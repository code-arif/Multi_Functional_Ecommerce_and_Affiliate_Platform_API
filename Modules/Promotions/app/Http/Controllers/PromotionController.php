<?php

namespace Modules\Promotions\Http\Controllers;

use Modules\Promotions\Services\PromotionService;
use Modules\Promotions\Http\Resources\PromotionResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController
{
    use ApiResponse;

    public function __construct(private PromotionService $promotionService) {}

    /**
     * GET /api/v1/promotions
     * Public — list active promotions.
     */
    public function index(Request $request): JsonResponse
    {
        $promotions = $this->promotionService->getActivePromotions(
            $request->only(['type', 'product_id'])
        );

        return $this->successResponse(PromotionResource::collection($promotions));
    }

    /**
     * GET /api/v1/promotions/flash-sales
     * Public — list active flash sales.
     */
    public function flashSales(): JsonResponse
    {
        $promotions = $this->promotionService->getActiveFlashSales();

        return $this->successResponse(PromotionResource::collection($promotions));
    }

    /**
     * GET /api/v1/promotions/upcoming
     * Public — list upcoming promotions.
     */
    public function upcoming(): JsonResponse
    {
        $promotions = $this->promotionService->getUpcomingPromotions();

        return $this->successResponse(PromotionResource::collection($promotions));
    }
}
