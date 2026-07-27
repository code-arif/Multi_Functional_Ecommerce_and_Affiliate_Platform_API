<?php

namespace Modules\AdminPanel\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\AdminPanel\Http\Controllers';
    protected string $moduleName = 'AdminPanel';
    protected string $moduleNameLower = 'adminpanel';
}
