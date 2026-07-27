<?php

namespace Modules\Auth\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Auth\Http\Controllers';
    protected string $moduleName = 'Auth';
    protected string $moduleNameLower = 'auth';
}
