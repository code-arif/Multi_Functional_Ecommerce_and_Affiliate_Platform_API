<?php

namespace Modules\Affiliate\Http\Controllers;

use Modules\Affiliate\Services\AffiliateService;
use Modules\Affiliate\Models\AffiliateEarning;
use Modules\Affiliate\Http\Resources\AffiliateProductResource;
use Modules\Affiliate\Http\Resources\AffiliateDashboardResource;
use Modules\Affiliate\Http\Resources\AffiliateEarningResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController
{
    use ApiResponse;

    public function __construct(private AffiliateService $affiliateService) {}

    /**
     * GET /api/v1/affiliate
     * Public — list active affiliate products.
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->affiliateService->getProducts(
            $request->only(['featured', 'search', 'platform', 'category_id', 'per_page'])
        );

        return $this->paginatedResponse(AffiliateProductResource::collection($products));
    }

    /**
     * GET /api/v1/affiliate/{slug}
     * Public — show affiliate product detail.
     */
    public function show(string $slug): JsonResponse
    {
        $product = $this->affiliateService->getProductBySlug($slug);
        $product->loadCount('clicks', 'conversions');

        return $this->successResponse(new AffiliateProductResource($product));
    }

    /**
     * POST /api/v1/affiliate/{slug}/click
     * Public — track affiliate click and redirect.
     */
    public function click(string $slug, Request $request): JsonResponse
    {
        $product = $this->affiliateService->getProductBySlug($slug);

        $this->affiliateService->trackClick(
            $product,
            $request->ip(),
            $request->userAgent(),
            $request->header('referer'),
            $request->user() // null if guest
        );

        return $this->successResponse([
            'url' => $product->affiliate_link,
        ], 'Redirecting...');
    }

    /**
     * GET /api/v1/affiliate/dashboard
     * Auth — get affiliate dashboard stats.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->affiliateService->getDashboard($user);

        return $this->successResponse(new AffiliateDashboardResource($stats));
    }

    /**
     * GET /api/v1/affiliate/earnings
     * Auth — get earning history.
     */
    public function earnings(Request $request): JsonResponse
    {
        $earnings = $this->affiliateService->getEarningsHistory(
            $request->user()->id,
            $request->only(['status', 'per_page'])
        );

        return $this->paginatedResponse(AffiliateEarningResource::collection($earnings));
    }
}
