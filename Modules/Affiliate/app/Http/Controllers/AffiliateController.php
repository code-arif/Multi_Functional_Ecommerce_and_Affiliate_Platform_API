<?php

namespace Modules\Affiliate\Http\Controllers;

use Modules\Affiliate\Services\AffiliateService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController
{
    use ApiResponse;

    public function __construct(private AffiliateService $affiliateService) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->affiliateService->getProducts($request->only(['featured', 'search']));
        return $this->paginatedResponse($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->affiliateService->getProductBySlug($slug);
        return $this->successResponse($product);
    }

    public function click(string $slug, Request $request): JsonResponse
    {
        $product = $this->affiliateService->getProductBySlug($slug);

        $this->affiliateService->trackClick(
            $product,
            $request->ip(),
            $request->userAgent(),
            $request->header('referer')
        );

        return $this->successResponse([
            'url' => $product->affiliate_link,
        ], 'Redirecting...');
    }
}
