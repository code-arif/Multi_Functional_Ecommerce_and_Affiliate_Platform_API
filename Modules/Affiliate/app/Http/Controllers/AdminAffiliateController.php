<?php

namespace Modules\Affiliate\Http\Controllers;

use Modules\Affiliate\Services\AffiliateService;
use Modules\Affiliate\Models\AffiliateConversion;
use Modules\Affiliate\Models\AffiliateEarning;
use Modules\Affiliate\Http\Resources\AffiliateProductResource;
use Modules\Affiliate\Http\Resources\AffiliateConversionResource;
use Modules\Affiliate\Http\Resources\AffiliateEarningResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAffiliateController
{
    use ApiResponse;

    public function __construct(private AffiliateService $affiliateService) {}

    /**
     * GET /api/v1/admin/affiliate/analytics
     * Get affiliate analytics overview.
     */
    public function analytics(): JsonResponse
    {
        $analytics = $this->affiliateService->getAnalytics();

        return $this->successResponse($analytics);
    }

    /**
     * GET /api/v1/admin/affiliate/conversions
     * List all conversions.
     */
    public function conversions(Request $request): JsonResponse
    {
        $query = \Modules\Affiliate\Models\AffiliateConversion::with(['product', 'user']);

        if ($request->status) $query->where('status', $request->status);
        if ($request->product_uuid) {
            $query->where('affiliate_product_id', \Modules\Affiliate\Models\AffiliateProduct::findByUuid($request->product_uuid)?->id);
        }

        return $this->paginatedResponse(AffiliateConversionResource::collection($query->latest()->paginate($request->per_page ?? 20)));
    }

    /**
     * POST /api/v1/admin/affiliate/conversions/{conversion}/approve
     * Approve a conversion (releases earnings after cooling period).
     */
    public function approveConversion(AffiliateConversion $conversion): JsonResponse
    {
        $conversion = $this->affiliateService->approveConversion($conversion);
        $conversion->load('product', 'user');

        return $this->successResponse(new AffiliateConversionResource($conversion), 'Conversion approved.');
    }

    /**
     * POST /api/v1/admin/affiliate/conversions/{conversion}/reject
     * Reject a conversion.
     */
    public function rejectConversion(AffiliateConversion $conversion): JsonResponse
    {
        $conversion = $this->affiliateService->rejectConversion($conversion);
        $conversion->load('product', 'user');

        return $this->successResponse(new AffiliateConversionResource($conversion), 'Conversion rejected.');
    }

    /**
     * GET /api/v1/admin/affiliate/earnings
     * List all earnings with filters.
     */
    public function earnings(Request $request): JsonResponse
    {
        $query = \Modules\Affiliate\Models\AffiliateEarning::with(['product', 'user']);

        if ($request->status) $query->where('status', $request->status);
        if ($request->user_id) $query->where('user_id', $request->user_id);

        return $this->paginatedResponse(AffiliateEarningResource::collection($query->latest()->paginate($request->per_page ?? 20)));
    }

    /**
     * POST /api/v1/admin/affiliate/earnings/mark-paid
     * Mark specific earnings as paid.
     */
    public function markAsPaid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'earning_uuids'   => 'required|array',
            'earning_uuids.*' => 'exists:affiliate_earnings,uuid',
        ]);

        $count = $this->affiliateService->markAsPaid($validated['earning_uuids']);

        return $this->successResponse(['marked_paid' => $count], "{$count} earnings marked as paid.");
    }
}
