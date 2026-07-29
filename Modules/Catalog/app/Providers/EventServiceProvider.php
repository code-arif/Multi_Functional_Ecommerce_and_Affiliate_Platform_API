<?php

namespace Modules\Catalog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Events\ProductDeleted;
use Modules\Catalog\Listeners\LogProductActivity;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Observers\BrandObserver;
use Modules\Catalog\Observers\CategoryObserver;
use Modules\Catalog\Observers\ProductObserver;

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

    protected $observers = [
        Product::class => [ProductObserver::class],
        Category::class => [CategoryObserver::class],
        Brand::class => [BrandObserver::class],
    ];
}
