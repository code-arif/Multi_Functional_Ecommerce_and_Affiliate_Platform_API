<?php

namespace Modules\Catalog\Observers;

use Modules\Product\Models\Product;;

class ProductObserver
{
    public function created(Product $product): void
    {
        // Invalidate product cache
        \Illuminate\Support\Facades\Cache::tags(['products'])->flush();
    }

    public function updated(Product $product): void
    {
        \Illuminate\Support\Facades\Cache::tags(['products'])->flush();
    }

    public function deleted(Product $product): void
    {
        \Illuminate\Support\Facades\Cache::tags(['products'])->flush();
    }
}
