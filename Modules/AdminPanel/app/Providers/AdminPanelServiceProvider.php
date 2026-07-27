<?php

namespace Modules\AdminPanel\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class AdminPanelServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AdminPanel';
    protected string $nameLower = 'adminpanel';

    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
