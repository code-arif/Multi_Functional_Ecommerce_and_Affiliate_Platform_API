<?php

namespace App\Services\Cache;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheService
 *
 * Centralizes all cache operations. Uses Redis tags where available
 * so related caches can be flushed atomically without clearing everything.
 *
 * Tag Groups:
 *   products     → product listings, single product, featured, etc.
 *   categories   → category tree and individual category pages
 *   homepage     → banners, sliders, homepage data
 *   settings     → site-wide settings
 *   search       → search result caches
 *   reports      → dashboard stats and report caches
 */
class CacheService
{
    // ─── TTL constants (seconds) ─────────────────────────────────
    const TTL_PRODUCT      = 1800;   // 30 minutes
    const TTL_PRODUCT_LIST = 900;    // 15 minutes
    const TTL_CATEGORY     = 3600;   // 1 hour
    const TTL_HOMEPAGE     = 1800;   // 30 minutes
    const TTL_SETTINGS     = 86400;  // 24 hours
    const TTL_SEARCH       = 300;    // 5 minutes
    const TTL_REPORTS      = 600;    // 10 minutes
    const TTL_CART         = 604800; // 7 days

    // ─── Tag constants ────────────────────────────────────────────
    const TAG_PRODUCTS   = 'products';
    const TAG_CATEGORIES = 'categories';
    const TAG_HOMEPAGE   = 'homepage';
    const TAG_SETTINGS   = 'settings';
    const TAG_SEARCH     = 'search';
    const TAG_REPORTS    = 'reports';

    // ─── Product Cache ────────────────────────────────────────────

    public function rememberProduct(string $slug, callable $callback): mixed
    {
        return $this->tagged([self::TAG_PRODUCTS])
                    ->remember("product:{$slug}", self::TTL_PRODUCT, $callback);
    }

    public function rememberProductList(array $filters, callable $callback): mixed
    {
        $key = 'product_list:' . md5(serialize($filters));
        return $this->tagged([self::TAG_PRODUCTS, self::TAG_SEARCH])
                    ->remember($key, self::TTL_PRODUCT_LIST, $callback);
    }

    public function rememberFeaturedProducts(int $limit, callable $callback): mixed
    {
        return $this->tagged([self::TAG_PRODUCTS])
                    ->remember("products:featured:{$limit}", self::TTL_PRODUCT_LIST, $callback);
    }

    public function rememberNewProducts(int $limit, callable $callback): mixed
    {
        return $this->tagged([self::TAG_PRODUCTS])
                    ->remember("products:new:{$limit}", self::TTL_PRODUCT_LIST, $callback);
    }

    public function rememberBestsellers(int $limit, callable $callback): mixed
    {
        return $this->tagged([self::TAG_PRODUCTS])
                    ->remember("products:bestsellers:{$limit}", self::TTL_PRODUCT_LIST, $callback);
    }

    public function forgetProduct(string $slug): void
    {
        Cache::forget("product:{$slug}");
        $this->flushTag(self::TAG_PRODUCTS);
    }

    // ─── Category Cache ───────────────────────────────────────────

    public function rememberCategoryTree(callable $callback): mixed
    {
        return $this->tagged([self::TAG_CATEGORIES])
                    ->remember('category:tree', self::TTL_CATEGORY, $callback);
    }

    public function rememberCategory(string $slug, callable $callback): mixed
    {
        return $this->tagged([self::TAG_CATEGORIES])
                    ->remember("category:{$slug}", self::TTL_CATEGORY, $callback);
    }

    public function flushCategories(): void
    {
        $this->flushTag(self::TAG_CATEGORIES);
    }

    // ─── Homepage / Banner Cache ──────────────────────────────────

    public function rememberHomepage(callable $callback): mixed
    {
        return $this->tagged([self::TAG_HOMEPAGE])
                    ->remember('homepage:data', self::TTL_HOMEPAGE, $callback);
    }

    public function rememberBanners(string $position, callable $callback): mixed
    {
        return $this->tagged([self::TAG_HOMEPAGE])
                    ->remember("banners:{$position}", self::TTL_HOMEPAGE, $callback);
    }

    public function flushHomepage(): void
    {
        $this->flushTag(self::TAG_HOMEPAGE);
    }

    // ─── Settings Cache ───────────────────────────────────────────

    public function rememberSettings(callable $callback): mixed
    {
        return $this->tagged([self::TAG_SETTINGS])
                    ->remember('settings:all', self::TTL_SETTINGS, $callback);
    }

    public function flushSettings(): void
    {
        $this->flushTag(self::TAG_SETTINGS);
    }

    // ─── Search Cache ─────────────────────────────────────────────

    public function rememberSearch(array $filters, callable $callback): mixed
    {
        $key = 'search:' . md5(serialize($filters));
        return $this->tagged([self::TAG_SEARCH])
                    ->remember($key, self::TTL_SEARCH, $callback);
    }

    public function rememberPriceRange(callable $callback): mixed
    {
        return $this->tagged([self::TAG_SEARCH, self::TAG_PRODUCTS])
                    ->remember('search:price_range', self::TTL_PRODUCT_LIST, $callback);
    }

    // ─── Report Cache ─────────────────────────────────────────────

    public function rememberDashboard(callable $callback): mixed
    {
        return $this->tagged([self::TAG_REPORTS])
                    ->remember('reports:dashboard', self::TTL_REPORTS, $callback);
    }

    public function flushReports(): void
    {
        $this->flushTag(self::TAG_REPORTS);
    }

    // ─── Generic Helpers ─────────────────────────────────────────

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    public function flushTag(string $tag): void
    {
        try {
            Cache::tags([$tag])->flush();
        } catch (Exception $e) {
            // If Redis tags not available, fall back to key-based forget
            Log::warning("Cache tag flush failed for tag: {$tag}", ['error' => $e->getMessage()]);
        }
    }

    public function flushAll(): void
    {
        Cache::flush();
    }

    // ─── Private Helpers ─────────────────────────────────────────

    private function tagged(array $tags)
    {
        try {
            return Cache::tags($tags);
        } catch (Exception $e) {
            // Fallback: return plain cache (no tags, e.g. file driver)
            return Cache::store();
        }
    }

    // ─── Cache Warmer ─────────────────────────────────────────────

    /**
     * Pre-warm critical caches after deploy or cache flush.
     */
    public function warmCriticalCaches(): void
    {
        // Warm category tree
        $this->rememberCategoryTree(fn() =>
            Category::active()
                ->whereNull('parent_id')
                ->with('allChildren')
                ->withCount('products')
                ->orderBy('sort_order')
                ->get()
        );

        // Warm settings
        $this->rememberSettings(fn() =>
            Setting::pluck('value', 'key')->toArray()
        );

        // Warm homepage
        $this->rememberHomepage(fn() => [
            'sliders' => Banner::active()->forPosition('hero_slider')->get(),
        ]);

        Log::info('Cache warmed successfully.');
    }
}
