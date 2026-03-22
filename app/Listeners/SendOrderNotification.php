<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Jobs\SendOrderConfirmationEmail;

class SendOrderNotification
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderConfirmationEmail::dispatch($event->order)->onQueue('emails');
    }
}
