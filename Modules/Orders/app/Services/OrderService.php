<?php

namespace Modules\Orders\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\ActivityLog;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Enums\PaymentStatus;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Events\OrderStatusUpdated;
use Modules\Orders\Exceptions\InvalidOrderTransitionException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Orders\Models\OrderStatusHistory;

class OrderService
{
    public function __construct(private InvoiceService $invoiceService)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Order placement
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create an order from a structured payload (used by the Checkout module,
     * which splits a single cart into one order per vendor).
     *
     * @param array{
     *     user_id?: int|null,
     *     vendor_id?: int|null,
     *     group_id?: string|null,
     *     order_number?: string|null,
     *     status?: string,
     *     subtotal: float,
     *     shipping_charge?: float,
     *     discount_amount?: float,
     *     coupon_discount?: float,
     *     tax_amount?: float,
     *     total_amount: float,
     *     coupon_code?: string|null,
     *     payment_method?: string,
     *     payment_status?: string,
     *     shipping_method?: string|null,
     *     shipping_name?: string,
     *     shipping_phone?: string,
     *     shipping_email?: string|null,
     *     shipping_address_line1?: string,
     *     shipping_address_line2?: string|null,
     *     shipping_city?: string,
     *     shipping_state?: string|null,
     *     shipping_postal_code?: string|null,
     *     shipping_country?: string,
     *     shipping_address?: string|null,
     *     billing_address?: string|null,
     *     customer_note?: string|null,
     *     tracking_token?: string|null,
     *     guest_email?: string|null,
     *     guest_token?: string|null,
     *     actor_type?: string,
     *     actor_name?: string|null,
     *     items?: array<int, array<string, mixed>>,
     * } $payload
     */
    public function placeOrder(array $payload): Order
    {
        $items = $payload['items'] ?? [];
        unset($payload['items']);

        $actorType = $payload['actor_type'] ?? 'system';
        $actorName = $payload['actor_name'] ?? null;
        unset($payload['actor_type'], $payload['actor_name']);

        $order = Order::create($payload);

        foreach ($items as $item) {
            $order->items()->create($item);
        }

        // Initial status history entry
        OrderStatusHistory::create([
            'order_id'         => $order->id,
            'old_status'       => null,
            'new_status'       => $order->status,
            'actor_type'       => $actorType,
            'changed_by'       => $order->user_id,
            'changed_by_name'  => $actorName,
            'notes'            => 'Order placed.',
            'notify_customer'  => true,
        ]);

        // Generate the invoice
        $this->invoiceService->generateFor($order);

        // Audit trail (Super Admin monitoring)
        $this->logActivity($order, 'created', [], ['status' => $order->status], $order->user_id);

        event(new OrderPlaced($order));

        Log::channel('orders')->info('Order placed', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'total'        => $order->total_amount,
        ]);

        return $order->load(['items', 'statusHistories', 'invoice']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Status machine
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Transition an order to a new status, validating the state machine,
     * recording history, syncing the invoice and firing events.
     */
    public function updateStatus(
        Order $order,
        string $toStatus,
        ?string $note = null,
        ?User $actor = null,
        string $actorType = 'system',
        bool $notifyCustomer = true,
    ): Order {
        $oldStatus = $order->status;
        $from = OrderStatus::fromValue($oldStatus);
        $to = OrderStatus::fromValue($toStatus);

        if (!$to) {
            throw InvalidOrderTransitionException::invalidTarget($toStatus);
        }

        if ($from && $from !== $to && !$from->canTransitionTo($to)) {
            throw InvalidOrderTransitionException::fromTo($from->label(), $to->label());
        }

        $updates = ['status' => $to->value];

        // Apply status timestamps
        $updates += match ($to) {
            OrderStatus::Confirmed => ['confirmed_at' => now()],
            OrderStatus::Processing => ['processed_at' => now()],
            OrderStatus::Shipped => ['shipped_at' => now()],
            OrderStatus::Delivered => ['delivered_at' => now()],
            OrderStatus::Cancelled => ['cancelled_at' => now(), 'cancel_reason' => $note],
            OrderStatus::Refunded => ['refunded_at' => now()],
            default => [],
        };

        // COD orders are paid once delivered
        if ($to === OrderStatus::Delivered && $order->payment_method === 'cod') {
            $updates['payment_status'] = PaymentStatus::Paid->value;
            $updates['paid_at'] = now();
        }

        // Restore stock when an order is cancelled
        if ($to === OrderStatus::Cancelled) {
            $this->restoreStock($order);
        }

        $order->update($updates);

        OrderStatusHistory::create([
            'order_id'        => $order->id,
            'old_status'      => $oldStatus,
            'new_status'      => $to->value,
            'actor_type'      => $actorType,
            'changed_by'      => $actor?->id,
            'changed_by_name' => $actor?->name,
            'notes'           => $note,
            'notify_customer' => $notifyCustomer,
        ]);

        $this->invoiceService->syncWithOrder($order->fresh());
        $this->logActivity($order, 'status_updated', ['status' => $oldStatus], ['status' => $to->value], $actor?->id);

        if ($notifyCustomer) {
            event(new OrderStatusUpdated($order, $oldStatus, $to->value, $actorType));
        }

        Log::channel('orders')->info('Order status updated', [
            'order_id'   => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $to->value,
            'actor'      => $actor?->email,
            'actor_type' => $actorType,
        ]);

        return $order->fresh()->load(['items', 'statusHistories', 'payment', 'invoice']);
    }

    /**
     * Direct cancellation (customer within the cancel window, or admin/vendor).
     */
    public function cancelOrder(Order $order, ?string $reason = null, ?User $actor = null, string $actorType = 'customer'): Order
    {
        if (!$order->can_be_cancelled) {
            throw new \DomainException('This order cannot be cancelled in its current state.');
        }

        return $this->updateStatus($order, OrderStatus::Cancelled->value, $reason ?: 'Cancelled.', $actor, $actorType);
    }

    public function refundOrder(Order $order, ?string $note = null, ?User $actor = null): Order
    {
        return $this->updateStatus($order, OrderStatus::Refunded->value, $note, $actor, 'admin');
    }

    public function markPaid(Order $order, ?User $actor = null): Order
    {
        $order->update([
            'payment_status' => PaymentStatus::Paid->value,
            'paid_at'        => now(),
        ]);

        $this->invoiceService->syncWithOrder($order->fresh());
        $this->logActivity($order, 'payment_marked_paid', ['payment_status' => 'pending'], ['payment_status' => 'paid'], $actor?->id);

        return $order->fresh()->load(['payment', 'invoice']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tracking & notes
    // ─────────────────────────────────────────────────────────────────────────

    public function updateTracking(Order $order, ?string $trackingNumber, ?string $carrier = null): Order
    {
        $order->update([
            'tracking_number'  => $trackingNumber,
            'shipping_carrier' => $carrier,
        ]);

        $this->logActivity($order, 'tracking_updated', [], [
            'tracking_number'  => $trackingNumber,
            'shipping_carrier' => $carrier,
        ]);

        return $order->fresh();
    }

    public function updateAdminNote(Order $order, ?string $note, User $actor): Order
    {
        $order->update(['admin_note' => $note]);

        $this->logActivity($order, 'note_updated', [], ['admin_note' => $note], $actor->id);

        return $order->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Query helpers
    // ─────────────────────────────────────────────────────────────────────────

    public function findForCustomer(int $userId, string $orderNumber): Order
    {
        $order = Order::with(['items', 'payment', 'statusHistories', 'cancelRequests', 'invoice', 'vendor'])
            ->where('order_number', $orderNumber)
            ->where('user_id', $userId)
            ->first();

        if (!$order) {
            throw new ModelNotFoundException('No order found.');
        }

        return $order;
    }

    public function getCustomerOrders(int $userId, array $filters = []): LengthAwarePaginator
    {
        return Order::with(['items', 'payment', 'vendor'])
            ->forUser($userId)
            ->byStatus($filters['status'] ?? null)
            ->byPaymentStatus($filters['payment_status'] ?? null)
            ->betweenDates($filters['from'] ?? null, $filters['to'] ?? null)
            ->latest('id')
            ->paginate($filters['per_page'] ?? config('orders.default_per_page', 15));
    }

    public function getVendorOrders(int $vendorId, array $filters = []): LengthAwarePaginator
    {
        return Order::with(['items', 'payment', 'user'])
            ->forVendor($vendorId)
            ->byStatus($filters['status'] ?? null)
            ->search($filters['search'] ?? null)
            ->betweenDates($filters['from'] ?? null, $filters['to'] ?? null)
            ->latest('id')
            ->paginate($filters['per_page'] ?? config('orders.default_per_page', 15));
    }

    public function getAdminOrders(array $filters = []): LengthAwarePaginator
    {
        return Order::with(['user', 'vendor', 'items', 'payment'])
            ->byStatus($filters['status'] ?? null)
            ->byPaymentStatus($filters['payment_status'] ?? null)
            ->when(
                !empty($filters['vendor_id']),
                fn ($q) => $q->forVendor($filters['vendor_id'])
            )
            ->search($filters['search'] ?? null)
            ->betweenDates($filters['from'] ?? null, $filters['to'] ?? null)
            ->latest('id')
            ->paginate($filters['per_page'] ?? config('orders.admin_per_page', 20));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return sold stock back to products/variants when an order is cancelled.
     */
    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;

            if (!$product || !$product->manage_stock) {
                continue;
            }

            $product->increment('stock_quantity', $item->quantity);
            $product->decrement('total_sold', $item->quantity);

            if ($product->stock_quantity > 0 && $product->stock_status === 'out_of_stock') {
                $product->update(['stock_status' => 'in_stock']);
            }

            if ($item->product_variant_id) {
                $item->variant?->increment('stock_quantity', $item->quantity);
            }
        }
    }

    /**
     * Write an entry into the global activity_logs table (module: orders)
     * so Super Admin can monitor every action on the platform.
     */
    private function logActivity(Order $order, string $action, array $old = [], array $new = [], ?int $actorId = null): void
    {
        try {
            ActivityLog::create([
                'user_id'      => $actorId,
                'module'       => 'orders',
                'action'       => $action,
                'subject_type' => Order::class,
                'subject_id'   => $order->id,
                'old_values'   => $old,
                'new_values'   => $new,
                'ip'           => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('orders')->warning('Failed to write activity log', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
