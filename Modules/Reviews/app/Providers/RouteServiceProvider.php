<?php

namespace Modules\Reviews\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Reviews\Http\Controllers';
    protected string $moduleName = 'Reviews';
    protected string $moduleNameLower = 'reviews';
}
