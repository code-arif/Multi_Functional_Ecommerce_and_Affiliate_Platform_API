<?php

namespace Modules\Notifications\Notifications;

use Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order #{$this->order->order_number} - Shipped")
            ->line("Your order #{$this->order->order_number} has been shipped!")
            ->line("Track your order: " . url('/orders/track/' . $this->order->tracking_token))
            ->action('Track Order', url('/orders/track/' . $this->order->tracking_token));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'tracking_token' => $this->order->tracking_token,
            'type'         => 'order_shipped',
        ];
    }
}
