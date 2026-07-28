<?php

namespace Modules\Shipping\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ShippingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Shipping';
    protected string $nameLower = 'shipping';

    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
    ];
}
