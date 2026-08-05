<?php

namespace Modules\Orders\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Events\OrderStatusUpdated;
use Modules\Orders\Listeners\SendOrderNotification;
use Modules\Orders\Listeners\SendShippingUpdateNotification;
use Modules\Orders\Models\Order;
use Modules\Orders\Policies\OrderPolicy;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        OrderPlaced::class => [
            SendOrderNotification::class,
        ],
        OrderStatusUpdated::class => [
            SendShippingUpdateNotification::class,
        ],
    ];

    /**
     * Register model policies.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Order::class => OrderPolicy::class,
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void
    {
    }
}
