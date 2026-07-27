<?php

namespace Modules\Cms\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Cms\Http\Controllers';
    protected string $moduleName = 'Cms';
    protected string $moduleNameLower = 'cms';
}
