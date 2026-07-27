<?php

namespace Modules\Finance\Providers;

use Modules\Orders\Events\OrderStatusUpdated;
use Modules\Finance\Listeners\CalculateCommissionOnDelivered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderStatusUpdated::class => [
            CalculateCommissionOnDelivered::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
