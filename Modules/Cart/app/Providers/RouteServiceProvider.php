<?php

namespace Modules\Cart\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Cart\Http\Controllers';
    protected string $moduleName = 'Cart';
    protected string $moduleNameLower = 'cart';
}
