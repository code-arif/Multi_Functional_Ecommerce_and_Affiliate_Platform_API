<?php

namespace Modules\Payments\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Payments\Http\Controllers';
    protected string $moduleName = 'Payments';
    protected string $moduleNameLower = 'payments';
}
