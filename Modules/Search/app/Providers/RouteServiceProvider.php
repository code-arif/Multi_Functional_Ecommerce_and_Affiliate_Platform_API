<?php

namespace Modules\Search\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Search\Http\Controllers';
    protected string $moduleName = 'Search';
    protected string $moduleNameLower = 'search';
}
