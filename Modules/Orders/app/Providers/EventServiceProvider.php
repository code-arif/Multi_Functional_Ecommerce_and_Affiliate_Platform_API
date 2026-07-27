<?php

namespace Modules\Orders\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Orders\Models\Order;
use Modules\Orders\Policies\OrderPolicy;
use Modules\Orders\Observers\OrderObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\Orders\Events\OrderPlaced::class => [
            \Modules\Orders\Listeners\SendOrderNotification::class,
        ],
        \Modules\Orders\Events\OrderStatusUpdated::class => [
            // Future: SendStatusUpdateNotification::class,
        ],
    ];

    protected $policies = [
        Order::class => OrderPolicy::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Order::observe(OrderObserver::class);
    }
}
