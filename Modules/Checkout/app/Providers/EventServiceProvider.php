<?php

namespace Modules\Checkout\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Future listeners can be added here:
        // \Modules\Checkout\Events\CheckoutProcessed::class => [
        //     \Modules\Checkout\Listeners\SendOrderConfirmation::class,
        // ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
