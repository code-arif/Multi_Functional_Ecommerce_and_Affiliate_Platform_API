<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("orders.{$this->order->order_number}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_number' => $this->order->order_number,
            'old_status'   => $this->oldStatus,
            'new_status'   => $this->newStatus,
        ];
    }
}
