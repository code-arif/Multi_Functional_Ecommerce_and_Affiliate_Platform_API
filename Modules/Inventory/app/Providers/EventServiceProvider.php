<?php

namespace Modules\Inventory\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Listeners\HandleLowStock;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        StockAdjusted::class => [
            HandleLowStock::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
