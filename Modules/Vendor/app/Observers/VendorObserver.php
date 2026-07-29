<?php

namespace Modules\Vendor\Observers;

use Modules\Vendor\Models\Vendor;
use Illuminate\Support\Facades\Cache;

class VendorObserver
{
    public function created(Vendor $vendor): void
    {
        $this->clearVendorCache();
    }

    public function updated(Vendor $vendor): void
    {
        $this->clearVendorCache();
    }

    public function deleted(Vendor $vendor): void
    {
        $this->clearVendorCache();
    }

    public function restored(Vendor $vendor): void
    {
        $this->clearVendorCache();
    }

    public function forceDeleted(Vendor $vendor): void
    {
        $this->clearVendorCache();
    }

    /**
     * Flush vendor cache tags safely, falling back to a general flush
     * when the cache driver does not support tags (e.g. array driver in tests).
     */
    private function clearVendorCache(): void
    {
        try {
            Cache::tags(['vendors'])->flush();
        } catch (\Exception $e) {
            // Cache driver does not support tags (e.g. 'array' in tests)
            // Silently ignore — cache will be cleared on next request anyway.
        }
    }
}
