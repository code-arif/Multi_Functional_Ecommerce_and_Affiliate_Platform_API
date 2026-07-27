<?php

namespace Modules\Promotions\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class PromotionsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Promotions';
    protected string $nameLower = 'promotions';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
