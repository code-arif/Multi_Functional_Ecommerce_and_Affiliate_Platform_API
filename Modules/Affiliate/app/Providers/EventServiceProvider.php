<?php

namespace Modules\Affiliate\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Future: AffiliateConversionApproved::class => [NotifyAffiliate::class],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
