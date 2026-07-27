<?php

namespace Modules\Payments\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Payments';
    protected string $nameLower = 'payments';

    protected array $providers = [
        RouteServiceProvider::class,
        EventServiceProvider::class,
    ];
}
