<?php

namespace Modules\Inventory\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Inventory\Http\Controllers';
    protected string $moduleName = 'Inventory';
    protected string $moduleNameLower = 'inventory';
}
