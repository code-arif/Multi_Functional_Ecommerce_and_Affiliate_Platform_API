<?php

namespace Modules\Orders\Listeners;

use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Events\OrderStatusUpdated;
use Modules\Orders\Jobs\SendShippingUpdateEmail;

class SendShippingUpdateNotification
{
    public function handle(OrderStatusUpdated $event): void
    {
        if ($event->toStatus !== OrderStatus::Shipped->value) {
            return;
        }

        SendShippingUpdateEmail::dispatch($event->order)->onQueue('emails');
    }
}
