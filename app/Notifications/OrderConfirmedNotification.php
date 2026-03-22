<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmedNotification extends Notification implements ShouldQueue
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
            ->subject("Order Confirmed - #{$this->order->order_number}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your order **#{$this->order->order_number}** has been placed successfully.")
            ->line("**Total Amount:** ৳{$this->order->total_amount}")
            ->line("**Payment Method:** " . strtoupper($this->order->payment_method))
            ->line("**Shipping To:** {$this->order->shipping_name}, {$this->order->shipping_city}")
            ->action('View Order', url("/orders/{$this->order->order_number}"))
            ->line('Thank you for shopping with us!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'         => 'order_confirmed',
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->order->total_amount,
            'message'      => "Order #{$this->order->order_number} confirmed.",
        ];
    }
}
