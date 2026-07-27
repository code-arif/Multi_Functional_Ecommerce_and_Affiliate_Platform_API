<?php

namespace Modules\Support\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Support\Http\Controllers';
    protected string $moduleName = 'Support';
    protected string $moduleNameLower = 'support';
}
