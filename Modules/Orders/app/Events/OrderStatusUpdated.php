<?php

namespace Modules\Orders\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $toStatus,
        public ?string $actorType = null,
    ) {
    }

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
            'new_status'   => $this->toStatus,
            'actor_type'   => $this->actorType,
        ];
    }
}
