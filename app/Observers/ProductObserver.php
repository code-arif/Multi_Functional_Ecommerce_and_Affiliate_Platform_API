<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Cache\CacheService;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    public function __construct(private CacheService $cache) {}

    public function created(Product $product): void
    {
        $this->cache->flushTag(CacheService::TAG_PRODUCTS);
        $this->cache->flushTag(CacheService::TAG_SEARCH);
        Log::channel('admin_actions')->info('Product created', ['id' => $product->id, 'name' => $product->name]);
    }

    public function updated(Product $product): void
    {
        // Forget the specific product cache
        $this->cache->forgetProduct($product->slug);

        // If slug changed, also forget the old slug
        if ($product->wasChanged('slug')) {
            $this->cache->forgetProduct($product->getOriginal('slug'));
        }

        // Flush list caches
        $this->cache->flushTag(CacheService::TAG_PRODUCTS);
        $this->cache->flushTag(CacheService::TAG_SEARCH);
    }

    public function deleted(Product $product): void
    {
        $this->cache->forgetProduct($product->slug);
        $this->cache->flushTag(CacheService::TAG_PRODUCTS);
        $this->cache->flushTag(CacheService::TAG_SEARCH);
        Log::channel('admin_actions')->info('Product deleted', ['id' => $product->id]);
    }
}
