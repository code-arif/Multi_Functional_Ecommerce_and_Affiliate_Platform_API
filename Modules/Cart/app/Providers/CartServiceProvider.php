<?php

namespace Modules\Cart\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CartServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Cart';
    protected string $nameLower = 'cart';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
