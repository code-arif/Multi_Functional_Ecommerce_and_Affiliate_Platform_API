<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GenerateSitemapCommand extends Command
{
    protected $signature   = 'sitemap:generate';
    protected $description = 'Generate and cache the XML sitemap';

    public function handle(SeoService $seoService): int
    {
        $this->info('Generating sitemap...');

        $xml = $seoService->generateSitemap();

        // Store as a file for direct Nginx serving
        Storage::disk('public')->put('sitemap.xml', $xml);

        // Also cache it in Redis
        Cache::put('sitemap_xml', $xml, now()->addHours(6));

        $urlCount = substr_count($xml, '<url>');
        $this->info("Sitemap generated with {$urlCount} URLs.");
        $this->info('   → Saved to: public/storage/sitemap.xml');

        return self::SUCCESS;
    }
}
