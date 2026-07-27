<?php

namespace Modules\Catalog\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Catalog';
    protected string $nameLower = 'catalog';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
