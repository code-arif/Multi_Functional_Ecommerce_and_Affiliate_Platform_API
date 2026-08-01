<?php

namespace Modules\Cart\Http\Controllers;

use Modules\Cart\Services\CompareService;
use Modules\Cart\Http\Resources\CompareListResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController
{
    use ApiResponse;

    public function __construct(private CompareService $compareService) {}

    /**
     * GET /api/v1/compare
     * Get the current compare list.
     */
    public function index(Request $request): JsonResponse
    {
        $list = $this->compareService->getCompareList(
            $request->user(),
            $request->header('X-Session-ID')
        );

        $list->load(['items.product.category', 'items.product.brand', 'items.product.primaryImage']);

        return $this->successResponse(new CompareListResource($list));
    }

    /**
     * POST /api/v1/compare/{product}
     * Add a product to compare list.
     */
    public function add(string $product, Request $request): JsonResponse
    {
        $list = $this->compareService->getCompareList(
            $request->user(),
            $request->header('X-Session-ID')
        );

        $result = $this->compareService->add($list, $product);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], null, 400);
        }

        return $this->successResponse(null, $result['message']);
    }

    /**
     * DELETE /api/v1/compare/{product}
     * Remove a product from compare list.
     */
    public function remove(string $product, Request $request): JsonResponse
    {
        $list = $this->compareService->getCompareList(
            $request->user(),
            $request->header('X-Session-ID')
        );

        $result = $this->compareService->remove($list, $product);

        return $this->successResponse(null, $result['message']);
    }

    /**
     * DELETE /api/v1/compare
     * Clear the entire compare list.
     */
    public function clear(Request $request): JsonResponse
    {
        $list = $this->compareService->getCompareList(
            $request->user(),
            $request->header('X-Session-ID')
        );

        $this->compareService->clear($list);

        return $this->noContentResponse('Compare list cleared.');
    }
}
