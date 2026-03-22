<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\Cache\CacheService;

class SettingObserver
{
    public function __construct(private CacheService $cache) {}

    public function created(Setting $setting): void  { $this->flush(); }
    public function updated(Setting $setting): void  { $this->flush(); }
    public function deleted(Setting $setting): void  { $this->flush(); }

    private function flush(): void
    {
        $this->cache->flushSettings();
        $this->cache->flushHomepage(); // settings affect homepage too
    }
}
