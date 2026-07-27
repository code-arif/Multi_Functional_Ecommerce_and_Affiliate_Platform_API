<?php

namespace Modules\Catalog\Http\Controllers;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Catalog\Http\Resources\ProductListResource;
use Modules\Reviews\Http\Resources\ReviewResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $products = Product::active()
            ->with(['category', 'brand', 'variants', 'primaryImage'])
            ->when($request->category, fn($q, $slug) =>
                $q->whereHas('category', fn($q) => $q->where('slug', $slug)))
            ->when($request->brand, fn($q, $slug) =>
                $q->whereHas('brand', fn($q) => $q->where('slug', $slug)))
            ->when($request->min_price, fn($q, $p) => $q->where('sale_price', '>=', $p)
                ->orWhere(fn($q) => $q->whereNull('sale_price')->where('price', '>=', $p)))
            ->when($request->max_price, fn($q, $p) => $q->where('sale_price', '<=', $p)
                ->orWhere(fn($q) => $q->whereNull('sale_price')->where('price', '<=', $p)))
            ->when($request->sort, function ($q, $sort) {
                match ($sort) {
                    'price_asc'  => $q->orderBy('price'),
                    'price_desc' => $q->orderByDesc('price'),
                    'newest'     => $q->latest(),
                    'popular'    => $q->orderByDesc('total_sold'),
                    'rating'     => $q->orderByDesc('average_rating'),
                    default      => $q->latest(),
                };
            }, fn($q) => $q->latest())
            ->paginate($request->per_page ?? config('ecommerce.pagination.products_per_page', 20));

        return $this->paginatedResponse(ProductListResource::collection($products));
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::active()
            ->with(['images', 'variants', 'attributes.values', 'category', 'brand', 'reviews.user'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->successResponse(new ProductResource($product));
    }

    public function featured(Request $request): JsonResponse
    {
        $products = Product::featured()
            ->with(['category', 'primaryImage'])
            ->limit($request->limit ?? 10)
            ->get();

        return $this->successResponse(ProductListResource::collection($products));
    }

    public function newArrivals(Request $request): JsonResponse
    {
        $products = Product::new()
            ->with(['category', 'primaryImage'])
            ->limit($request->limit ?? 10)
            ->get();

        return $this->successResponse(ProductListResource::collection($products));
    }

    public function bestsellers(Request $request): JsonResponse
    {
        $products = Product::bestseller()
            ->with(['category', 'primaryImage'])
            ->limit($request->limit ?? 10)
            ->get();

        return $this->successResponse(ProductListResource::collection($products));
    }

    public function related(string $slug, Request $request): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['category', 'primaryImage'])
            ->limit($request->limit ?? 8)
            ->get();

        return $this->successResponse(ProductListResource::collection($related));
    }
}
