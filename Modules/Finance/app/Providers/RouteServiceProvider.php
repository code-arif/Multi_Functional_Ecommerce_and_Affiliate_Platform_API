<?php

namespace Modules\Finance\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Finance\Http\Controllers';
    protected string $moduleName = 'Finance';
    protected string $moduleNameLower = 'finance';
}
