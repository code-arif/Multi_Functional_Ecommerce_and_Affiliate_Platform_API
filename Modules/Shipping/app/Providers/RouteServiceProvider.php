<?php

namespace Modules\Shipping\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Shipping\Http\Controllers';
    protected string $moduleName = 'Shipping';
    protected string $moduleNameLower = 'shipping';
}
