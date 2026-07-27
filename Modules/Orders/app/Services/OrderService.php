<?php

namespace Modules\Orders\Services;

use Modules\Orders\Models\Order;
use Modules\Orders\Models\Invoice;
use Modules\Orders\Models\CancelRequest;
use Modules\Orders\Events\OrderStatusUpdated;
use Modules\Auth\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private const STATUS_FLOW = [
        'pending'    => ['confirmed', 'cancelled'],
        'confirmed'  => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['delivered'],
        'delivered'  => ['refunded'],
        'cancelled'  => [],
        'refunded'   => [],
    ];

    // ─── User Order Queries ───────────────────────────────────────

    public function getUserOrders(User $user, array $filters = [])
    {
        $query = Order::with('items')->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        return $query->latest()->paginate(
            $filters['per_page'] ?? config('ecommerce.pagination.orders_per_page', 15)
        );
    }

    public function getOrderByNumber(string $orderNumber, ?User $user = null): Order
    {
        $query = Order::with('items', 'statusHistories', 'payment', 'invoice')
            ->where('order_number', $orderNumber);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->firstOrFail();
    }

    public function getOrderByToken(string $token): Order
    {
        return Order::with('items')
            ->where('tracking_token', $token)
            ->firstOrFail();
    }

    // ─── Vendor Order Queries ─────────────────────────────────────

    public function getVendorOrders(int $vendorId, array $filters = [])
    {
        $query = Order::with('user:id,name,email')
            ->where('vendor_id', $vendorId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                  ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$filters['search']}%"));
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getVendorOrderDetail(int $vendorId, string $orderNumber): Order
    {
        return Order::with('items', 'statusHistories', 'user')
            ->where('vendor_id', $vendorId)
            ->where('order_number', $orderNumber)
            ->firstOrFail();
    }

    // ─── Status Management ────────────────────────────────────────

    public function updateStatus(Order $order, string $newStatus, ?string $note = null, ?int $changedBy = null): Order
    {
        $fromStatus = $order->status;

        $allowed = self::STATUS_FLOW[$fromStatus] ?? [];
        if (!in_array($newStatus, $allowed)) {
            abort(400, "Cannot change from '{$fromStatus}' to '{$newStatus}'. Allowed: " . implode(', ', $allowed));
        }

        DB::transaction(function () use ($order, $newStatus, $note, $changedBy, $fromStatus) {
            $order->update(['status' => $newStatus]);

            $timestampField = match ($newStatus) {
                'shipped'   => 'shipped_at',
                'delivered' => 'delivered_at',
                'cancelled' => 'cancelled_at',
                'refunded'  => 'refunded_at',
                default     => null,
            };

            if ($timestampField) {
                $order->update([$timestampField => now()]);
            }

            if ($newStatus === 'shipped' && !$order->relationLoaded('invoice') || !$order->invoice) {
                $this->generateInvoice($order);
            }

            $order->statusHistories()->create([
                'from_status'      => $fromStatus,
                'to_status'        => $newStatus,
                'changed_by'       => $changedBy ?? request()->user()?->id,
                'changed_by_name'  => request()->user()?->name ?? 'System',
                'notes'            => $note,
            ]);
        });

        OrderStatusUpdated::dispatch($order, $fromStatus, $newStatus);

        return $order->fresh();
    }

    public function isValidTransition(string $fromStatus, string $toStatus): bool
    {
        $allowed = self::STATUS_FLOW[$fromStatus] ?? [];
        return in_array($toStatus, $allowed);
    }

    public function getAvailableStatuses(Order $order): array
    {
        return self::STATUS_FLOW[$order->status] ?? [];
    }

    // ─── Order Cancellation ───────────────────────────────────────

    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            abort(400, 'Order cannot be cancelled in its current state.');
        }

        $fromStatus = $order->status;

        DB::transaction(function () use ($order, $reason, $fromStatus) {
            $order->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            $order->statusHistories()->create([
                'from_status'     => $fromStatus,
                'to_status'       => 'cancelled',
                'changed_by'      => request()->user()?->id,
                'changed_by_name' => request()->user()?->name ?? 'System',
                'notes'           => $reason ?? 'Cancelled by user.',
            ]);

            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);
                    $item->product->decrement('total_sold', $item->quantity);
                }
            }
        });

        OrderStatusUpdated::dispatch($order, $fromStatus, 'cancelled');

        Log::info('Order cancelled', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $order->fresh();
    }

    public function requestCancellation(Order $order, User $user, string $reason): CancelRequest
    {
        if (!in_array($order->status, ['pending', 'confirmed', 'processing', 'shipped'])) {
            abort(400, 'Cannot request cancellation for this order status.');
        }

        if (CancelRequest::where('order_id', $order->id)->where('status', 'pending')->exists()) {
            abort(400, 'A cancellation request is already pending for this order.');
        }

        return CancelRequest::create([
            'order_id' => $order->id,
            'user_id'  => $user->id,
            'reason'   => $reason,
            'status'   => 'pending',
        ]);
    }

    public function approveCancellation(CancelRequest $cancelRequest, User $admin, ?string $response = null): Order
    {
        $order = $cancelRequest->order;

        DB::transaction(function () use ($cancelRequest, $admin, $response, $order) {
            $cancelRequest->update([
                'status'        => 'approved',
                'admin_response' => $response,
                'reviewed_by'   => $admin->id,
                'reviewed_at'   => now(),
            ]);

            $this->cancelOrder($order, $cancelRequest->reason);
        });

        return $order->fresh();
    }

    public function rejectCancellation(CancelRequest $cancelRequest, User $admin, string $response): CancelRequest
    {
        $cancelRequest->update([
            'status'        => 'rejected',
            'admin_response' => $response,
            'reviewed_by'   => $admin->id,
            'reviewed_at'   => now(),
        ]);

        return $cancelRequest->fresh();
    }

    // ─── Invoice ──────────────────────────────────────────────────

    public function generateInvoice(Order $order): Invoice
    {
        return Invoice::create([
            'order_id'       => $order->id,
            'invoice_number' => 'INV-' . strtoupper(Str::random(10)),
            'subtotal'       => $order->subtotal,
            'shipping_cost'  => $order->shipping_cost ?? 0,
            'discount_amount' => $order->discount_amount ?? 0,
            'tax_amount'     => $order->tax_amount ?? 0,
            'total'          => $order->total,
            'paid_amount'    => 0,
            'due_amount'     => $order->total,
            'status'         => 'pending',
            'issued_at'      => now(),
            'due_at'         => now()->addDays(30),
        ]);
    }
}
