<?php

namespace Modules\Notifications\Providers;

use Modules\Core\Providers\RouteServiceProvider as BaseRouteServiceProvider;

class RouteServiceProvider extends BaseRouteServiceProvider
{
    protected string $moduleNamespace = 'Modules\Notifications\Http\Controllers';
    protected string $moduleName = 'Notifications';
    protected string $moduleNameLower = 'notifications';
}
