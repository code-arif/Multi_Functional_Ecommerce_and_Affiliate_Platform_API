<?php

namespace Modules\Orders\Listeners;

use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Jobs\SendOrderConfirmationEmail;

class SendOrderNotification
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderConfirmationEmail::dispatch($event->order)->onQueue('emails');
    }
}
