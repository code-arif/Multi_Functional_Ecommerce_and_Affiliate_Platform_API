<?php

namespace Modules\Search\Services;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Category;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    public function search(string $query, array $filters = [], int $perPage = 20)
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

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $products->where('category_id', $filters['category_id']);
        }

        // Apply brand filter
        if (!empty($filters['brand_id'])) {
            $products->where('brand_id', $filters['brand_id']);
        }

        // Apply price range
        if (isset($filters['min_price'])) {
            $products->where(function ($q) use ($filters) {
                $q->where('sale_price', '>=', $filters['min_price'])
                    ->orWhere(function ($q) use ($filters) {
                        $q->whereNull('sale_price')->where('price', '>=', $filters['min_price']);
                    });
            });
        }
        if (isset($filters['max_price'])) {
            $products->where(function ($q) use ($filters) {
                $q->where('sale_price', '<=', $filters['max_price'])
                    ->orWhere(function ($q) use ($filters) {
                        $q->whereNull('sale_price')->where('price', '<=', $filters['max_price']);
                    });
            });
        }

        // Sort
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'price_asc'  => $products->orderBy('price'),
            'price_desc' => $products->orderByDesc('price'),
            'popular'    => $products->orderByDesc('total_sold'),
            'rating'     => $products->orderByDesc('average_rating'),
            default      => $products->latest(),
        };

        return $products->paginate($perPage);
    }

    public function suggestions(string $query): array
    {
        return Cache::remember("search_suggestions:{$query}", 3600, function () use ($query) {
            return Product::active()
                ->where('name', 'like', "%{$query}%")
                ->limit(10)
                ->pluck('name')
                ->toArray();
        });
    }

    public function priceRange(array $filters = []): array
    {
        $query = Product::active();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        return [
            'min' => (float) (clone $query)->min('price'),
            'max' => (float) (clone $query)->max('price'),
        ];
    }

    public function getFacets(string $query): array
    {
        $products = Product::active()->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%");
        });

        return [
            'categories' => Category::whereIn('id', (clone $products)->pluck('category_id')->unique())
                ->select('id', 'name', 'slug')
                ->get(),
            'price_range' => [
                'min' => (float) (clone $products)->min('price'),
                'max' => (float) (clone $products)->max('price'),
            ],
        ];
    }
}
