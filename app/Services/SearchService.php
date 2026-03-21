<?php

namespace App\Services;

use App\Models\Product;
use App\Models\AffiliateProduct;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    public function search(array $filters)
    {
        $cacheKey = 'search_' . md5(serialize($filters));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters) {
            return Product::with(['images', 'category', 'brand'])
                ->active()
                ->when(!empty($filters['q']), fn($q) =>
                    $q->where(function ($query) use ($filters) {
                        $query->where('name', 'like', "%{$filters['q']}%")
                              ->orWhere('short_description', 'like', "%{$filters['q']}%")
                              ->orWhere('sku', 'like', "%{$filters['q']}%")
                              ->orWhereJsonContains('tags', $filters['q']);
                    })
                )
                ->when(!empty($filters['category']), fn($q) =>
                    $q->whereHas('category', fn($q) =>
                        $q->where('slug', $filters['category'])
                          ->orWhereHas('parent', fn($p) =>
                              $p->where('slug', $filters['category'])))
                )
                ->when(!empty($filters['brand']), fn($q) =>
                    $q->whereHas('brand', fn($q) => $q->where('slug', $filters['brand'])))
                ->when(isset($filters['min_price']), fn($q) =>
                    $q->where('price', '>=', $filters['min_price']))
                ->when(isset($filters['max_price']), fn($q) =>
                    $q->where('price', '<=', $filters['max_price']))
                ->when(!empty($filters['rating']), fn($q) =>
                    $q->where('average_rating', '>=', $filters['rating']))
                ->when(!empty($filters['in_stock']), fn($q) =>
                    $q->where('stock_status', 'in_stock'))
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
                ->paginate($filters['per_page'] ?? 20);
        });
    }

    public function getPriceRange(): array
    {
        return Cache::remember('price_range', now()->addHour(), function () {
            return [
                'min' => (float) Product::active()->min('price'),
                'max' => (float) Product::active()->max('price'),
            ];
        });
    }

    public function getSuggestions(string $query, int $limit = 5): array
    {
        $products = Product::active()
            ->where('name', 'like', "{$query}%")
            ->select('id', 'name', 'slug', 'thumbnail', 'price', 'sale_price')
            ->limit($limit)
            ->get();

        return $products->map(fn($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'slug'  => $p->slug,
            'image' => $p->thumbnail_url,
            'price' => $p->current_price,
        ])->toArray();
    }
}
