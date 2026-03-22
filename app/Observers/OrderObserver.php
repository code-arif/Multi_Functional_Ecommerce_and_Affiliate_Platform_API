<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(private CacheService $cache) {}

    public function created(Order $order): void
    {
        $this->cache->flushReports();
        Log::channel('orders')->info('Order created', [
            'order_number' => $order->order_number,
            'total'        => $order->total_amount,
            'method'       => $order->payment_method,
        ]);
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged(['status', 'payment_status', 'total_amount'])) {
            $this->cache->flushReports();
            Log::channel('orders')->info('Order updated', [
                'order_number' => $order->order_number,
                'changes'      => $order->getChanges(),
            ]);
        }
    }
}
