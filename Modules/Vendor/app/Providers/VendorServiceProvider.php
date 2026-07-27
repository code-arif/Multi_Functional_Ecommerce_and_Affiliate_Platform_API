<?php

namespace Modules\Vendor\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class VendorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Vendor';
    protected string $nameLower = 'vendor';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
