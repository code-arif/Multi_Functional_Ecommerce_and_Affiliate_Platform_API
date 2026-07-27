<?php

namespace Modules\Affiliate\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Affiliate\Http\Controllers';
    protected string $moduleName = 'Affiliate';
    protected string $moduleNameLower = 'affiliate';
}
