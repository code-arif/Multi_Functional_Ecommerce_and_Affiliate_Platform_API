<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Services\Cache\CacheService;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(private CacheService $cache) {}

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->cache->rememberSearch($filters, function () use ($filters) {
            return Product::with(['images', 'category', 'brand'])
                ->active()
                ->when(
                    !empty($filters['q']),
                    fn($q) =>
                    $q->where(
                        fn($sub) =>
                        $sub->where('name', 'like', "%{$filters['q']}%")
                            ->orWhere('short_description', 'like', "%{$filters['q']}%")
                            ->orWhere('sku', 'like', "%{$filters['q']}%")
                            ->orWhereJsonContains('tags', $filters['q'])
                    )
                )
                ->when(
                    !empty($filters['category']),
                    fn($q) =>
                    $q->whereHas(
                        'category',
                        fn($cq) =>
                        $cq->where('slug', $filters['category'])
                            ->orWhereHas(
                                'parent',
                                fn($pq) =>
                                $pq->where('slug', $filters['category'])
                            )
                    )
                )
                ->when(
                    !empty($filters['brand']),
                    fn($q) =>
                    $q->whereHas(
                        'brand',
                        fn($bq) =>
                        $bq->where('slug', $filters['brand'])
                    )
                )
                ->when(
                    isset($filters['min_price']),
                    fn($q) =>
                    $q->where('price', '>=', (float) $filters['min_price'])
                )
                ->when(
                    isset($filters['max_price']),
                    fn($q) =>
                    $q->where('price', '<=', (float) $filters['max_price'])
                )
                ->when(
                    !empty($filters['rating']),
                    fn($q) =>
                    $q->where('average_rating', '>=', (float) $filters['rating'])
                )
                ->when(
                    !empty($filters['in_stock']),
                    fn($q) =>
                    $q->where('stock_status', 'in_stock')
                )
                ->when(!empty($filters['sort']), function ($q) use ($filters) {
                    match ($filters['sort']) {
                        'price_asc'  => $q->orderBy('price', 'asc'),
                        'price_desc' => $q->orderBy('price', 'desc'),
                        'newest'     => $q->orderBy('created_at', 'desc'),
                        'popularity' => $q->orderBy('total_sold', 'desc'),
                        'rating'     => $q->orderBy('average_rating', 'desc'),
                        default      => $q->orderBy('created_at', 'desc'),
                    };
                }, fn($q) => $q->orderBy('created_at', 'desc'))
                ->paginate((int) ($filters['per_page'] ?? 20));
        });
    }

    public function getSuggestions(string $query, int $limit = 8): array
    {
        return $this->cache->remember(
            'suggestions:' . md5($query),
            60,
            fn() => Product::active()
                ->where('name', 'like', "{$query}%")
                ->select('id', 'name', 'slug', 'thumbnail', 'price', 'sale_price')
                ->orderBy('total_sold', 'desc')
                ->limit($limit)
                ->get()
                ->map(fn($p) => [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'slug'  => $p->slug,
                    'image' => $p->thumbnail_url,
                    'price' => (float) $p->current_price,
                ])
                ->toArray()
        );
    }

    public function getPriceRange(): array
    {
        return $this->cache->rememberPriceRange(fn() => [
            'min' => (float) Product::active()->min('price'),
            'max' => (float) Product::active()->max('price'),
        ]);
    }

    public function getFacets(string $keyword = ''): array
    {
        return $this->cache->remember('facets:' . md5($keyword), 300, function () use ($keyword) {
            $baseQuery   = Product::active()
                ->when($keyword, fn($q) => $q->where('name', 'like', "%{$keyword}%"));

            $categoryIds = (clone $baseQuery)->pluck('category_id')->unique()->filter();
            $brandIds    = (clone $baseQuery)->pluck('brand_id')->unique()->filter();

            return [
                'categories'  => Category::active()->whereIn('id', $categoryIds)->select('id', 'name', 'slug')->get(),
                'brands'      => Brand::active()->whereIn('id', $brandIds)->select('id', 'name', 'slug')->get(),
                'price_range' => [
                    'min' => (float) (clone $baseQuery)->min('price'),
                    'max' => (float) (clone $baseQuery)->max('price'),
                ],
                'ratings'     => [1, 2, 3, 4, 5],
            ];
        });
    }
}
