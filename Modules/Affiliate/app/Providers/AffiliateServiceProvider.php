<?php

namespace Modules\Affiliate\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class AffiliateServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Affiliate';
    protected string $nameLower = 'affiliate';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
