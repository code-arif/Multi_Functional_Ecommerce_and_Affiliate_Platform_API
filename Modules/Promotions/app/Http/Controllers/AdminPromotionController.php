<?php

namespace Modules\Promotions\Http\Controllers;

use \Modules\Catalog\Models\Category;
use \Modules\Product\Models\Product;
use \Modules\Vendor\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Promotions\Http\Resources\PromotionResource;
use Modules\Promotions\Models\Promotion;
use Modules\Promotions\Services\PromotionService;

class AdminPromotionController
{
    use ApiResponse;

    public function __construct(private PromotionService $promotionService) {}

    /**
     * GET /api/v1/admin/promotions
     * List all promotions (admin view).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Promotion::withCount('usages');

        if ($request->type) $query->where('type', $request->type);
        if ($request->is_active !== null) $query->where('is_active', $request->is_active === 'true');

        $promotions = $query->orderBy('sort_order')->latest()->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(PromotionResource::collection($promotions));
    }

    /**
     * POST /api/v1/admin/promotions
     * Create a new promotion.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string|max:2000',
            'type'              => 'required|in:flash_sale,buy_x_get_y,tiered_discount,seasonal,free_shipping',
            'discount_type'     => 'required|in:percentage,fixed',
            'discount_value'    => 'required|numeric|min:0',
            'maximum_discount'  => 'nullable|numeric|min:0',
            'min_quantity'      => 'nullable|integer|min:1',
            'free_quantity'     => 'nullable|integer|min:1',
            'discount_on'       => 'nullable|in:cheapest,all',
            'tiers'             => 'nullable|array',
            'tiers.*.from'      => 'required|integer|min:1',
            'tiers.*.value'     => 'required|numeric|min:0',
            'applies_to'        => 'required|in:all,products,categories,vendors',
            'product_uuids'     => 'nullable|array',
            'product_uuids.*'   => 'exists:products,uuid',
            'category_uuids'    => 'nullable|array',
            'category_uuids.*'  => 'exists:categories,uuid',
            'vendor_uuids'      => 'nullable|array',
            'vendor_uuids.*'    => 'exists:vendors,uuid',
            'usage_limit'       => 'nullable|integer|min:1',
            'usage_per_user'    => 'nullable|integer|min:1',
            'is_active'         => 'boolean',
            'starts_at'         => 'nullable|date',
            'ends_at'           => 'nullable|date|after:starts_at',
            'badge_text'        => 'nullable|string|max:50',
            'badge_color'       => 'nullable|string|max:7',
            'sort_order'        => 'nullable|integer|min:0',
        ]);

        $promotion = $this->promotionService->createPromotion($this->mapUuids($validated));

        return $this->createdResponse(new PromotionResource($promotion), 'Promotion created.');
    }

    /**
     * GET /api/v1/admin/promotions/{promotion}
     * Show a single promotion.
     */
    public function show(Promotion $promotion): JsonResponse
    {
        $promotion->loadCount('usages');
        return $this->successResponse(new PromotionResource($promotion));
    }

    /**
     * PUT /api/v1/admin/promotions/{promotion}
     * Update a promotion.
     */
    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'sometimes|string|max:255',
            'description'       => 'nullable|string|max:2000',
            'type'              => 'sometimes|in:flash_sale,buy_x_get_y,tiered_discount,seasonal,free_shipping',
            'discount_type'     => 'sometimes|in:percentage,fixed',
            'discount_value'    => 'sometimes|numeric|min:0',
            'maximum_discount'  => 'nullable|numeric|min:0',
            'min_quantity'      => 'nullable|integer|min:1',
            'free_quantity'     => 'nullable|integer|min:1',
            'discount_on'       => 'nullable|in:cheapest,all',
            'tiers'             => 'nullable|array',
            'applies_to'        => 'sometimes|in:all,products,categories,vendors',
            'product_uuids'     => 'nullable|array',
            'category_uuids'    => 'nullable|array',
            'vendor_uuids'      => 'nullable|array',
            'usage_limit'       => 'nullable|integer|min:1',
            'usage_per_user'    => 'nullable|integer|min:1',
            'is_active'         => 'boolean',
            'starts_at'         => 'nullable|date',
            'ends_at'           => 'nullable|date|after:starts_at',
            'badge_text'        => 'nullable|string|max:50',
            'badge_color'       => 'nullable|string|max:7',
            'sort_order'        => 'nullable|integer|min:0',
        ]);

        $promotion = $this->promotionService->updatePromotion($promotion, $this->mapUuids($validated));

        return $this->successResponse(new PromotionResource($promotion), 'Promotion updated.');
    }

    /**
     * POST /api/v1/admin/promotions/{promotion}/toggle
     * Toggle a promotion's active status.
     */
    public function toggle(Promotion $promotion): JsonResponse
    {
        $promotion = $this->promotionService->toggleActive($promotion);

        $status = $promotion->is_active ? 'activated' : 'deactivated';
        return $this->successResponse(new PromotionResource($promotion), "Promotion {$status}.");
    }

    /**
     * DELETE /api/v1/admin/promotions/{promotion}
     * Delete a promotion.
     */
    public function destroy(Promotion $promotion): JsonResponse
    {
        $this->promotionService->deletePromotion($promotion);

        return $this->successResponse(null, 'Promotion deleted.');
    }

    /**
     * Map public uuid arrays to internal id arrays (products/categories/vendors).
     */
    private function mapUuids(array $data): array
    {
        if (isset($data['product_uuids'])) {
            $data['product_ids'] = Product::whereIn('uuid', $data['product_uuids'])->pluck('id')->all();
            unset($data['product_uuids']);
        }
        if (isset($data['category_uuids'])) {
            $data['category_ids'] = Category::whereIn('uuid', $data['category_uuids'])->pluck('id')->all();
            unset($data['category_uuids']);
        }
        if (isset($data['vendor_uuids'])) {
            $data['vendor_ids'] = Vendor::whereIn('uuid', $data['vendor_uuids'])->pluck('id')->all();
            unset($data['vendor_uuids']);
        }
        return $data;
    }

    /**
     * GET /api/v1/admin/promotions/analytics
     * Get promotion analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $analytics = $this->promotionService->getAnalytics($request->only(['type']));

        return $this->successResponse($analytics);
    }
}
