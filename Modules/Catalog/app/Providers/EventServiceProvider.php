<?php

namespace Modules\Catalog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Events\ProductDeleted;
use Modules\Catalog\Listeners\LogProductActivity;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Observers\BrandObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ProductCreated::class => [
            LogProductActivity::class,
        ],
        ProductUpdated::class => [
            LogProductActivity::class,
        ],
        ProductDeleted::class => [
            LogProductActivity::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        Brand::observe(BrandObserver::class);
    }
}
