<?php

namespace App\Observers;

use App\Models\Banner;
use App\Services\Cache\CacheService;

class BannerObserver
{
    public function __construct(private CacheService $cache) {}

    public function created(Banner $banner): void
    {
        $this->cache->flushHomepage();
    }
    public function updated(Banner $banner): void
    {
        $this->cache->flushHomepage();
    }
    public function deleted(Banner $banner): void
    {
        $this->cache->flushHomepage();
    }
}
