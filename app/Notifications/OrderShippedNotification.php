<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Order #{$this->order->order_number} Has Shipped! 🚚")
            ->view('emails.orders.shipped', ['order' => $this->order]);
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'order_shipped',
            'order_id'        => $this->order->id,
            'order_number'    => $this->order->order_number,
            'tracking_number' => $this->order->tracking_number,
            'message'         => "Order #{$this->order->order_number} has been shipped.",
        ];
    }
}
