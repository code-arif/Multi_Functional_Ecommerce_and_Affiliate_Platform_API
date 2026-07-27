<?php

namespace Modules\Cart\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Cart\Models\Cart;
use Modules\Cart\Policies\CartPolicy;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Future listeners can be added here
    ];

    protected $policies = [
        Cart::class => CartPolicy::class,
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
