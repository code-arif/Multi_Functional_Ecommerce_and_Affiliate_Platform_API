<?php

namespace App\Console\Commands;

use App\Services\Cache\CacheService;
use Illuminate\Console\Command;

class WarmCacheCommand extends Command
{
    protected $signature   = 'cache:warm';
    protected $description = 'Pre-warm critical application caches (categories, settings, homepage)';

    public function handle(CacheService $cache): int
    {
        $this->info('Warming caches...');
        $steps = [
            'Category tree'  => fn() => $cache->flushCategories(),
            'Settings'       => fn() => $cache->flushSettings(),
            'Homepage data'  => fn() => $cache->flushHomepage(),
        ];

        $bar = $this->output->createProgressBar(count($steps));
        $bar->start();

        foreach ($steps as $label => $step) {
            $step();
            $bar->advance();
        }

        $cache->warmCriticalCaches();
        $bar->finish();

        $this->newLine();
        $this->info('Cache warmed successfully.');

        return self::SUCCESS;
    }
}
