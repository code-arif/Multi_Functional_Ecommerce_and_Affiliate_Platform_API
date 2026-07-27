<?php

namespace Modules\Support\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SupportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Support';
    protected string $nameLower = 'support';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
