<?php

namespace Modules\Product\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Product\Models\Product;

class ProductObserver
{
    /**
     * Handle the ProductObserver "created" event.
     */
      public function created(Product $product): void
    {
        // Invalidate product cache
        Cache::tags(['products'])->flush();
    }

    /**
     * Handle the ProductObserver "updated" event.
     */
     public function updated(Product $product): void
    {
        Cache::tags(['products'])->flush();
    }

    /**
     * Handle the ProductObserver "deleted" event.
     */
    public function deleted(Product $product): void
    {
        Cache::tags(['products'])->flush();
    }

    /**
     * Handle the ProductObserver "restored" event.
     */
    public function restored(Product $product): void
    {
        Cache::tags(['products'])->flush();
    }

    /**
     * Handle the ProductObserver "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        Cache::tags(['products'])->flush();
    }
}

