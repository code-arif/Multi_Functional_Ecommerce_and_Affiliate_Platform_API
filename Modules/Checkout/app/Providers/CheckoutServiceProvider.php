<?php

namespace Modules\Checkout\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CheckoutServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Checkout';
    protected string $nameLower = 'checkout';

    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
    ];
}
