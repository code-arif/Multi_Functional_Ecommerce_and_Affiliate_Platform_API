<?php

namespace Modules\Vendor\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Vendor\Http\Controllers';
    protected string $moduleName = 'Vendor';
    protected string $moduleNameLower = 'vendor';
}
