<?php

namespace Modules\Vendor\Observers;

use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\Cache;

class VendorObserver
{
    public function created(Vendor $vendor): void
    {
        Cache::tags(['vendors'])->flush();
    }

    public function updated(Vendor $vendor): void
    {
        Cache::tags(['vendors'])->flush();
    }

    public function deleted(Vendor $vendor): void
    {
        Cache::tags(['vendors'])->flush();
    }

    public function restored(Vendor $vendor): void
    {
        Cache::tags(['vendors'])->flush();
    }

    public function forceDeleted(Vendor $vendor): void
    {
        Cache::tags(['vendors'])->flush();
    }
}
