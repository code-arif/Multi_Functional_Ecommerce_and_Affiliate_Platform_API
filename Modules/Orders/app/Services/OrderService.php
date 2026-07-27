<?php

namespace Modules\Orders\Services;

use Modules\Orders\Models\Order;
use Modules\Auth\Models\User;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function getUserOrders(User $user, array $filters = [])
    {
        $query = $user->orders()->with('items');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(
            $filters['per_page'] ?? config('ecommerce.pagination.orders_per_page', 15)
        );
    }

    public function getOrderByNumber(string $orderNumber, ?User $user = null): ?Order
    {
        $query = Order::with('items', 'statusHistories', 'payment')
            ->where('order_number', $orderNumber);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->firstOrFail();
    }

    public function getOrderByToken(string $token): ?Order
    {
        return Order::with('items')
            ->where('tracking_token', $token)
            ->firstOrFail();
    }

    public function cancelOrder(Order $order, ?string $reason = null): Order
    {
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            abort(400, 'Order cannot be cancelled in its current state.');
        }

        $order->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);

        $order->statusHistories()->create([
            'from_status'      => $order->getOriginal('status'),
            'to_status'        => 'cancelled',
            'changed_by'       => request()->user()?->id,
            'changed_by_name'  => request()->user()?->name ?? 'System',
            'notes'            => $reason ?? 'Cancelled by user.',
        ]);

        // Restore stock
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock_quantity', $item->quantity);
                $item->product->decrement('total_sold', $item->quantity);
            }
        }

        Log::info('Order cancelled', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $order->fresh();
    }

    public function updateStatus(Order $order, string $status, ?string $note = null, ?int $changedBy = null): Order
    {
        $fromStatus = $order->status;

        $order->update(['status' => $status]);

        $timestamps = [
            'shipped'   => 'shipped_at',
            'delivered' => 'delivered_at',
            'cancelled' => 'cancelled_at',
        ];

        if (isset($timestamps[$status])) {
            $order->update([$timestamps[$status] => now()]);
        }

        $order->statusHistories()->create([
            'from_status'      => $fromStatus,
            'to_status'        => $status,
            'changed_by'       => $changedBy ?? request()->user()?->id,
            'changed_by_name'  => request()->user()?->name ?? 'System',
            'notes'            => $note,
        ]);

        return $order->fresh();
    }
}
