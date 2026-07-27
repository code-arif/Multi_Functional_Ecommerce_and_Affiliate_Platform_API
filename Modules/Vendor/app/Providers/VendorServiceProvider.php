<?php

namespace Modules\Vendor\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Observers\VendorObserver;

class VendorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Vendor';
    protected string $nameLower = 'vendor';

    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Register observer
        Vendor::observe(VendorObserver::class);
    }
}
