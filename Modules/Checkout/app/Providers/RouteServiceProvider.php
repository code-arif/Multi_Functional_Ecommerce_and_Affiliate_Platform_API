<?php

namespace Modules\Checkout\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Checkout\Http\Controllers';
    protected string $moduleName = 'Checkout';
    protected string $moduleNameLower = 'checkout';
}
