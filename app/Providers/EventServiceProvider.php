<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Listeners\SendOrderNotification;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            SendOrderNotification::class,
        ],
    ];

    public function boot(): void {}
}
