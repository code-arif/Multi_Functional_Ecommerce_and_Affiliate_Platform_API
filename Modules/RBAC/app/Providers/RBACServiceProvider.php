<?php

namespace Modules\RBAC\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class RBACServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'RBAC';
    protected string $nameLower = 'rbac';

    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
    ];
}
