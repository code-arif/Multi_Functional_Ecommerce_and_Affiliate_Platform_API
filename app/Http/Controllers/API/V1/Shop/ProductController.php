<?php

namespace App\Http\Controllers\API\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(private ProductService $productService) {}

    /**
     * GET /api/v1/products
     */
    public function index(Request $request): JsonResponse
    {
        $filters  = $request->only([
            'category', 'brand', 'min_price', 'max_price',
            'rating', 'search', 'sort', 'per_page',
            'featured', 'new', 'in_stock',
        ]);

        $products = $this->productService->getProducts($filters);

        return $this->paginatedResponse(
            ProductListResource::collection($products),
            'Products fetched successfully.'
        );
    }

    /**
     * GET /api/v1/products/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->productService->getBySlug($slug);

        return $this->successResponse(
            new ProductResource($product),
            'Product fetched successfully.'
        );
    }

    /**
     * GET /api/v1/products/{slug}/related
     */
    public function related(string $slug): JsonResponse
    {
        $product = $this->productService->getBySlug($slug);
        $related = $this->productService->getRelated($product);

        return $this->successResponse(
            ProductListResource::collection($related),
            'Related products fetched.'
        );
    }

    /**
     * GET /api/v1/products/featured
     */
    public function featured(Request $request): JsonResponse
    {
        $products = $this->productService->getFeatured($request->limit ?? 10);

        return $this->successResponse(
            ProductListResource::collection($products),
            'Featured products fetched.'
        );
    }

    /**
     * GET /api/v1/products/new-arrivals
     */
    public function newArrivals(Request $request): JsonResponse
    {
        $products = $this->productService->getNew($request->limit ?? 10);

        return $this->successResponse(
            ProductListResource::collection($products),
            'New arrivals fetched.'
        );
    }

    /**
     * GET /api/v1/products/bestsellers
     */
    public function bestsellers(Request $request): JsonResponse
    {
        $products = $this->productService->getBestsellers($request->limit ?? 10);

        return $this->successResponse(
            ProductListResource::collection($products),
            'Bestsellers fetched.'
        );
    }
}
