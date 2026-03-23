<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("⚠️ Low Stock Alert: {$this->product->name}")
            ->greeting('Admin Alert')
            ->line("**{$this->product->name}** is running low on stock.")
            ->line("Current Stock: **{$this->product->stock_quantity} units**")
            ->line("Threshold: {$this->product->low_stock_threshold} units")
            ->action('Manage Product', url("/admin/products/{$this->product->id}"))
            ->line('Please restock to avoid stockouts.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'       => 'low_stock',
            'product_id' => $this->product->id,
            'product'    => $this->product->name,
            'stock'      => $this->product->stock_quantity,
            'message'    => "Low stock: {$this->product->name} ({$this->product->stock_quantity} left)",
        ];
    }
}
