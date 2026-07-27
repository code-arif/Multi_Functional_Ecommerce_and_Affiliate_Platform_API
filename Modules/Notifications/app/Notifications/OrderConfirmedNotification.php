<?php

namespace Modules\Notifications\Notifications;

use Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmedNotification extends Notification
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
            ->subject("Order #{$this->order->order_number} - Confirmed")
            ->line("Your order #{$this->order->order_number} has been confirmed.")
            ->line("Total: {$this->order->total}")
            ->action('View Order', url('/orders/' . $this->order->order_number));
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'total'        => $this->order->total,
            'type'         => 'order_confirmed',
        ];
    }
}
