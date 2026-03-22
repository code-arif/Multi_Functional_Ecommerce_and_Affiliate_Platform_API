<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\OrderConfirmedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $email = $this->order->user?->email ?? $this->order->guest_email;
        if (!$email) return;

        if ($this->order->user) {
            $this->order->user->notify(new OrderConfirmedNotification($this->order));
        }
    }

    public function queue(): string
    {
        return 'emails';
    }
}
