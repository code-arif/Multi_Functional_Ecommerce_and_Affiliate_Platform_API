<?php

namespace Modules\Catalog\Observers;

use Modules\Catalog\Models\Brand;
use Illuminate\Support\Facades\Cache;

class BrandObserver
{
    public function created(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }

    public function updated(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }

    public function deleted(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }

    public function restored(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }

    public function forceDeleted(Brand $brand): void
    {
        Cache::tags(['brands'])->flush();
    }
}
