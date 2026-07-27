<?php

namespace Modules\Core\Services\Cache;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    public function tags(array $tags): \Illuminate\Cache\TaggedCache
    {
        return Cache::tags($tags);
    }

    public function flush(): void
    {
        Cache::flush();
    }
}
