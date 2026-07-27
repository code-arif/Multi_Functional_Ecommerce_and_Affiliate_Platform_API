<?php

namespace Modules\Promotions\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Promotions\Http\Controllers';
    protected string $moduleName = 'Promotions';
    protected string $moduleNameLower = 'promotions';
}
