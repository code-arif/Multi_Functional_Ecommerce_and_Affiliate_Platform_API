<?php

namespace Modules\Orders\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Orders\Http\Controllers';
    protected string $moduleName = 'Orders';
    protected string $moduleNameLower = 'orders';
}
