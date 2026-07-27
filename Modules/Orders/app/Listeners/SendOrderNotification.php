<?php

namespace Modules\Orders\Listeners;

use Modules\Orders\Events\OrderPlaced;
use Modules\Notifications\Models\Notification;

class SendOrderNotification
{
    public function handle(OrderPlaced $event): void
    {
        // In-app notification
        $event->order->user->notify(
            new \Modules\Notifications\Notifications\OrderConfirmedNotification($event->order)
        );
    }
}
