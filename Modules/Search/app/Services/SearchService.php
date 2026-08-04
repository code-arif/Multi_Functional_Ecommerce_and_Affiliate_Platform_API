<?php

namespace Modules\Search\Services;

use Modules\Product\Models\Product;;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Brand;
use Modules\Search\Models\SearchLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(
        private ElasticsearchService $elasticsearch,
    ) {}

    /**
     * Search products using Elasticsearch with automatic DB fallback.
     */
    public function search(string $query, array $filters = [], int $perPage = 20): array|LengthAwarePaginator
    {
        $startTime = microtime(true);

        if ($this->elasticsearch->isAvailable()) {
            $from = (($filters['page'] ?? 1) - 1) * $perPage;
            $result = $this->elasticsearch->search($query, $filters, $from, $perPage);

            if (!isset($result['error'])) {
                $duration = (microtime(true) - $startTime) * 1000;
                $this->logSearch($query, $filters, $result['total'], $duration);
                return $result;
            }

            Log::warning('Elasticsearch search failed, falling back to DB: ' . ($result['error'] ?? ''));
        }

        // Fallback to database LIKE search
        return $this->databaseSearch($query, $filters, $perPage);
    }

    /**
     * Get autocomplete suggestions.
     */
    public function suggestions(string $query): array
    {
        if ($this->elasticsearch->isAvailable()) {
            $esSuggestions = $this->elasticsearch->suggest($query, config('search.search.suggestions_count', 10));
            if (!empty($esSuggestions)) {
                return $esSuggestions;
            }
        }

        return Cache::remember("search_suggestions:{$query}", 3600, function () use ($query) {
            return Product::active()
                ->where('name', 'like', "{$query}%")
                ->limit(config('search.search.suggestions_count', 10))
                ->pluck('name')
                ->toArray();
        });
    }

    /**
     * Get price range for products matching optional filters.
     */
    public function priceRange(array $filters = []): array
    {
        $query = Product::active();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $priceField = 'price';
        return [
            'min' => (float) (clone $query)->min($priceField),
            'max' => (float) (clone $query)->max($priceField),
        ];
    }

    /**
     * Get faceted search data for a keyword.
     */
    public function getFacets(string $keyword = ''): array
    {
        return Cache::remember('facets:' . md5($keyword), 300, function () use ($keyword) {
            $baseQuery = Product::active()
                ->when($keyword, fn($q) => $q->where('name', 'like', "%{$keyword}%"));

            $categoryIds = (clone $baseQuery)->pluck('category_id')->unique()->filter();
            $brandIds = (clone $baseQuery)->pluck('brand_id')->unique()->filter();

            return [
                'categories'  => Category::active()->whereIn('id', $categoryIds)
                    ->select('id', 'name', 'slug')->get(),
                'brands'      => Brand::active()->whereIn('id', $brandIds)
                    ->select('id', 'name', 'slug')->get(),
                'price_range' => [
                    'min' => (float) (clone $baseQuery)->min('price'),
                    'max' => (float) (clone $baseQuery)->max('price'),
                ],
                'ratings' => [1, 2, 3, 4, 5],
            ];
        });
    }

    /**
     * Get popular search queries.
     */
    public function popularSearches(int $limit = 10): array
    {
        return SearchLog::popular($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get searches with no results (for search optimization).
     */
    public function zeroResultSearches(int $limit = 20): array
    {
        return SearchLog::withoutResults()
            ->selectRaw('normalized_query, COUNT(*) as count, MAX(created_at) as last_searched')
            ->groupBy('normalized_query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // ─── Database Fallback ────────────────────────────────────────

    /**
     * Database-based search fallback when Elasticsearch is unavailable.
     */
    private function databaseSearch(string $query, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $products = Product::active()
            ->with(['category', 'brand', 'variants'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhere('short_description', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('tags', 'like', "%{$query}%");
            });

        if (!empty($filters['category_id'])) {
            $products->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $products->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['min_price'])) {
            $products->where('price', '>=', (float) $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $products->where('price', '<=', (float) $filters['max_price']);
        }
        if (!empty($filters['in_stock'])) {
            $products->where('stock_status', 'in_stock');
        }
        if (!empty($filters['rating'])) {
            $products->where('average_rating', '>=', (float) $filters['rating']);
        }

        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'price_asc'  => $products->orderBy('price', 'asc'),
            'price_desc' => $products->orderBy('price', 'desc'),
            'newest'     => $products->latest(),
            'popular'    => $products->orderByDesc('total_sold'),
            'rating'     => $products->orderByDesc('average_rating'),
            default      => $products->latest(),
        };

        return $products->paginate($perPage);
    }

    // ─── Search Logging ───────────────────────────────────────────

    /**
     * Log a search query for analytics.
     */
    private function logSearch(string $query, array $filters, int $resultsCount, float $durationMs): void
    {
        try {
            SearchLog::create([
                'query'            => $query,
                'normalized_query' => SearchLog::normalize($query),
                'session_id'       => session()->getId() ?? request()->header('X-Session-ID'),
                'ip_address'       => request()->ip(),
                'filters'          => !empty($filters) ? $filters : null,
                'results_count'    => $resultsCount,
                'search_duration_ms' => round($durationMs, 2),
                'has_results'      => $resultsCount > 0,
                'source'           => request()->header('X-Source', 'web'),
            ]);
        } catch (\Throwable $e) {
            // Don't let logging failures affect search results
            Log::warning('Search log failed: ' . $e->getMessage());
        }
    }

    /**
     * Get search analytics data.
     */
    public function getAnalytics(string $period = '7_days'): array
    {
        $days = match ($period) {
            '24h'   => 1,
            '7_days' => 7,
            '30_days' => 30,
            default  => 7,
        };

        $since = now()->subDays($days);

        return [
            'total_searches' => SearchLog::where('created_at', '>=', $since)->count(),
            'unique_queries' => SearchLog::where('created_at', '>=', $since)
                ->distinct('normalized_query')->count('normalized_query'),
            'zero_results'   => SearchLog::where('created_at', '>=', $since)
                ->where('has_results', false)->count(),
            'avg_duration_ms' => (float) SearchLog::where('created_at', '>=', $since)
                ->avg('search_duration_ms'),
            'popular'         => SearchLog::popular(10)->where('created_at', '>=', $since)->get()->toArray(),
            'zero_result_queries' => SearchLog::withoutResults()
                ->where('created_at', '>=', $since)
                ->selectRaw('normalized_query, COUNT(*) as count')
                ->groupBy('normalized_query')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }
}
