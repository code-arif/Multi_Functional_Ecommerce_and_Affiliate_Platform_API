<?php

namespace Modules\Orders\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Events\OrderStatusUpdated;
use Modules\Orders\Listeners\SendOrderNotification;
use Modules\Orders\Models\Order;
use Modules\Orders\Observers\OrderObserver;
use Modules\Orders\Policies\OrderPolicy;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            SendOrderNotification::class,
        ],
        OrderStatusUpdated::class => [
            // Future: SendStatusUpdateNotification::class,
        ],
    ];

    protected $policies = [
        Order::class => OrderPolicy::class,
    ];

    protected $observers = [
        Order::class => [OrderObserver::class],
    ];
}
