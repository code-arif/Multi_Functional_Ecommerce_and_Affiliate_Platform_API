<?php

namespace Modules\Orders\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Notifications\OrderShippedNotification;
use Modules\Orders\Models\Order;
use Throwable;

class SendShippingUpdateEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(public Order $order)
    {
    }

    public function handle(): void
    {
        try {
            if ($this->order->user) {
                $this->order->user->notify(new OrderShippedNotification($this->order));
            }

            Log::channel('orders')->info('Shipping email sent', [
                'order_number' => $this->order->order_number,
            ]);
        } catch (Exception $e) {
            Log::channel('orders')->error('Shipping email failed', [
                'order_number' => $this->order->order_number,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function queue(): string
    {
        return 'emails';
    }

    public function failed(Throwable $e): void
    {
        Log::channel('orders')->error('Shipping email job permanently failed', [
            'order_number' => $this->order->order_number,
            'error'        => $e->getMessage(),
        ]);
    }
}
