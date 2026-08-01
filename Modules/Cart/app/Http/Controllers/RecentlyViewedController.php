<?php

namespace Modules\Cart\Http\Controllers;

use Modules\Cart\Services\RecentlyViewedService;
use Modules\Cart\Http\Resources\RecentlyViewedResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecentlyViewedController
{
    use ApiResponse;

    public function __construct(private RecentlyViewedService $recentlyViewedService) {}

    /**
     * GET /api/v1/recently-viewed
     * Get recently viewed products.
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->recentlyViewedService->getRecent(
            $request->user(),
            $request->header('X-Session-ID'),
            10
        );

        return $this->successResponse($products);
    }

    /**
     * POST /api/v1/recently-viewed/{product}
     * Track a product view.
     */
    public function track(string $product, Request $request): JsonResponse
    {
        $productModel = \Modules\Catalog\Models\Product::findByUuidOrFail($product);

        $this->recentlyViewedService->track(
            $productModel,
            $request->user(),
            $request->header('X-Session-ID')
        );

        return $this->successResponse(null, 'Product view tracked.');
    }
}
