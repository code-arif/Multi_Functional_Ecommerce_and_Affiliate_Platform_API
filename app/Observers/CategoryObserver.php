<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cache\CacheService;

class CategoryObserver
{
    public function __construct(private CacheService $cache) {}

    public function created(Category $category): void
    {
        $this->flush();
    }
    public function updated(Category $category): void
    {
        $this->flush();
    }
    public function deleted(Category $category): void
    {
        $this->flush();
    }
    public function restored(Category $category): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        $this->cache->flushCategories();
        $this->cache->flushTag(CacheService::TAG_PRODUCTS); // products include category info
    }
}
