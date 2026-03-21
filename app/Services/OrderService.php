<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function updateStatus(
        Order $order,
        string $newStatus,
        ?string $comment = null,
        bool $notifyCustomer = true,
        ?int $adminId = null
    ): Order {
        $oldStatus = $order->status;

        $updates = ['status' => $newStatus];

        if ($newStatus === 'shipped') {
            $updates['shipped_at'] = now();
        } elseif ($newStatus === 'delivered') {
            $updates['delivered_at'] = now();
            $updates['payment_status'] = $order->payment_method === 'cod' ? 'paid' : $order->payment_status;
        } elseif ($newStatus === 'cancelled') {
            $updates['cancelled_at'] = now();
            // Restore stock
            $this->restoreStock($order);
        }

        $order->update($updates);

        OrderStatusHistory::create([
            'order_id'        => $order->id,
            'updated_by'      => $adminId,
            'old_status'      => $oldStatus,
            'new_status'      => $newStatus,
            'comment'         => $comment,
            'notify_customer' => $notifyCustomer,
        ]);

        if ($notifyCustomer) {
            event(new OrderStatusUpdated($order, $oldStatus, $newStatus));
        }

        Log::channel('orders')->info('Order status updated', [
            'order_id'   => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'admin_id'   => $adminId,
        ]);

        return $order->fresh()->load(['items', 'payment', 'statusHistories']);
    }

    public function updateTracking(Order $order, string $trackingNumber, string $carrier): Order
    {
        $order->update([
            'tracking_number'  => $trackingNumber,
            'shipping_carrier' => $carrier,
        ]);

        return $order->fresh();
    }

    public function cancelOrder(Order $order, ?int $userId = null): Order
    {
        if (!$order->can_be_cancelled) {
            throw new \Exception('This order cannot be cancelled in its current state.');
        }

        return $this->updateStatus($order, 'cancelled', 'Cancelled by customer.', true, $userId);
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = $item->product;
            if (!$product || !$product->manage_stock) continue;

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
}
