<?php

namespace Modules\Search\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SearchServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Search';
    protected string $nameLower = 'search';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
