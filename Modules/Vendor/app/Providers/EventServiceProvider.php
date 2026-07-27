<?php

namespace Modules\Vendor\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Vendor\Events\VendorRegistered;
use Modules\Vendor\Events\VendorApproved;
use Modules\Vendor\Events\VendorRejected;
use Modules\Vendor\Listeners\SendVendorNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        VendorRegistered::class => [
            SendVendorNotification::class,
        ],
        VendorApproved::class => [
            SendVendorNotification::class,
        ],
        VendorRejected::class => [
            SendVendorNotification::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
