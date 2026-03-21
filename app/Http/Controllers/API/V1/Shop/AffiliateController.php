<?php

namespace App\Http\Controllers\API\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\AffiliateProductResource;
use App\Models\AffiliateProduct;
use App\Services\AffiliateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function __construct(private AffiliateService $affiliateService) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->affiliateService->getProducts($request->all());
        return $this->paginatedResponse(
            AffiliateProductResource::collection($products),
            'Affiliate products fetched.'
        );
    }

    public function show(string $slug): JsonResponse
    {
        $product = AffiliateProduct::active()->where('slug', $slug)->with('category')->firstOrFail();
        return $this->successResponse(new AffiliateProductResource($product));
    }

    /**
     * POST /api/v1/affiliate/{slug}/click — Track and redirect
     */
    public function click(Request $request, string $slug): JsonResponse
    {
        $product = AffiliateProduct::active()->where('slug', $slug)->firstOrFail();

        $this->affiliateService->trackClick(
            $product,
            $request->user()?->id,
            $request->ip(),
            $request->userAgent()
        );

        return $this->successResponse([
            'affiliate_link' => $product->affiliate_link,
        ], 'Redirect URL returned.');
    }
}
