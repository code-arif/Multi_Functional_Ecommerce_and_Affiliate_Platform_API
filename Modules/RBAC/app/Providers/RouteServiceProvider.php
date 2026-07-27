<?php

namespace Modules\RBAC\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\RBAC\Http\Controllers';
    protected string $moduleName = 'RBAC';
    protected string $moduleNameLower = 'rbac';
}
