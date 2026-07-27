<?php

namespace Modules\Orders\Observers;

use Modules\Orders\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        // Activity log
        \Illuminate\Support\Facades\Log::info('Order created', [
            'order_number' => $order->order_number,
            'user_id'      => $order->user_id,
            'total'        => $order->total,
        ]);
    }

    public function updated(Order $order): void
    {
        if ($order->isDirty('status')) {
            \Illuminate\Support\Facades\Log::info('Order status changed', [
                'order_number' => $order->order_number,
                'from'         => $order->getOriginal('status'),
                'to'           => $order->status,
            ]);
        }
    }
}
