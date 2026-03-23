<?php

namespace App\Jobs;

use App\Services\Cache\CacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WarmCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CacheService $cache): void
    {
        $cache->warmCriticalCaches();
    }

    public function queue(): string { return 'default'; }
}
