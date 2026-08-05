<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Order events are registered by the Orders module (Modules\Orders\Providers\EventServiceProvider).
    }
}
