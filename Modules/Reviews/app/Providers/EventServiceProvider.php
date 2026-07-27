<?php

namespace Modules\Reviews\Providers;

use Modules\Reviews\Models\Review;
use Modules\Reviews\Policies\ReviewPolicy;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $policies = [
        Review::class => ReviewPolicy::class,
    ];

    protected $listen = [
        // Future: ReviewSubmitted::class => [NotifyAdmin::class],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
