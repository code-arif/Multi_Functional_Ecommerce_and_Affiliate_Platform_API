<?php

namespace Modules\Orders\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Notifications\OrderConfirmedNotification;
use Modules\Orders\Models\Order;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Order $order)
    {
    }

    public function handle(): void
    {
        $email = $this->order->user?->email ?? $this->order->guest_email;

        if (!$email) {
            return;
        }

        if ($this->order->user) {
            $this->order->user->notify(new OrderConfirmedNotification($this->order));
        }

        Log::channel('orders')->info('Order confirmation email queued', [
            'order_number' => $this->order->order_number,
        ]);
    }

    public function queue(): string
    {
        return 'emails';
    }
}
