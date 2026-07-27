<?php

namespace Modules\Catalog\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Catalog\Http\Controllers';
    protected string $moduleName = 'Catalog';
    protected string $moduleNameLower = 'catalog';
}
