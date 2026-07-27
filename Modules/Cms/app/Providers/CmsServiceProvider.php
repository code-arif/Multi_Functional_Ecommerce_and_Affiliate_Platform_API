<?php

namespace Modules\Cms\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class CmsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Cms';
    protected string $nameLower = 'cms';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
