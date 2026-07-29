<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Promotions\Models\Coupon;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorCouponController
{
    use ApiResponse;

    /**
     * GET /api/v1/vendor/coupons
     * List coupons created by this vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }
        if ($vendor->status !== 'active') {
            return $this->errorResponse('Your vendor account is not active.', null, 403);
        }

        $coupons = Coupon::withCount('usages')
            ->where('vendor_id', $vendor->id)
            ->when($request->search, fn($q) => $q->where('code', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('is_active', $request->status === 'active'))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($coupons->through(fn($c) => [
            'id'                    => $c->id,
            'code'                  => $c->code,
            'description'           => $c->description,
            'type'                  => $c->type,
            'value'                 => (float) $c->value,
            'minimum_order_amount'  => (float) $c->minimum_order_amount,
            'maximum_discount'      => (float) $c->maximum_discount,
            'usage_limit'           => $c->usage_limit,
            'usage_limit_per_user'  => $c->usage_limit_per_user,
            'used_count'            => $c->used_count,
            'usages_count'          => $c->usages_count,
            'is_active'             => $c->is_active,
            'is_valid'              => $c->is_valid,
            'starts_at'             => $c->starts_at,
            'expires_at'            => $c->expires_at,
            'created_at'            => $c->created_at,
        ]));
    }

    /**
     * POST /api/v1/vendor/coupons
     * Create a new coupon for this vendor.
     */
    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $validated = $request->validate([
            'code'                  => 'required|string|max:50|unique:coupons,code',
            'description'           => 'nullable|string|max:500',
            'type'                  => 'required|in:percentage,fixed',
            'value'                 => 'required|numeric|min:0',
            'minimum_order_amount'  => 'nullable|numeric|min:0',
            'maximum_discount'      => 'nullable|numeric|min:0',
            'usage_limit'           => 'nullable|integer|min:1',
            'usage_limit_per_user'  => 'nullable|integer|min:1',
            'is_active'             => 'nullable|boolean',
            'starts_at'             => 'nullable|date',
            'expires_at'            => 'nullable|date|after_or_equal:starts_at',
        ]);

        $coupon = Coupon::create(array_merge($validated, [
            'vendor_id' => $vendor->id,
            'is_active' => $validated['is_active'] ?? true,
        ]));

        return $this->createdResponse([
            'id'          => $coupon->id,
            'code'        => $coupon->code,
            'type'        => $coupon->type,
            'value'       => (float) $coupon->value,
            'is_active'   => $coupon->is_active,
            'starts_at'   => $coupon->starts_at,
            'expires_at'  => $coupon->expires_at,
        ], 'Coupon created.');
    }

    /**
     * PUT /api/v1/vendor/coupons/{coupon}
     * Update a vendor's coupon.
     */
    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($coupon->vendor_id !== $vendor->id) {
            return $this->errorResponse('Coupon not found.', null, 404);
        }

        $validated = $request->validate([
            'code'                  => 'nullable|string|max:50|unique:coupons,code,' . $coupon->id,
            'description'           => 'nullable|string|max:500',
            'type'                  => 'nullable|in:percentage,fixed',
            'value'                 => 'nullable|numeric|min:0',
            'minimum_order_amount'  => 'nullable|numeric|min:0',
            'maximum_discount'      => 'nullable|numeric|min:0',
            'usage_limit'           => 'nullable|integer|min:1',
            'usage_limit_per_user'  => 'nullable|integer|min:1',
            'is_active'             => 'nullable|boolean',
            'starts_at'             => 'nullable|date',
            'expires_at'            => 'nullable|date|after_or_equal:starts_at',
        ]);

        $coupon->update($validated);

        return $this->successResponse([
            'id'          => $coupon->id,
            'code'        => $coupon->code,
            'type'        => $coupon->type,
            'value'       => (float) $coupon->value,
            'is_active'   => $coupon->is_active,
            'starts_at'   => $coupon->starts_at,
            'expires_at'  => $coupon->expires_at,
        ], 'Coupon updated.');
    }

    /**
     * DELETE /api/v1/vendor/coupons/{coupon}
     * Delete a vendor's coupon.
     */
    public function destroy(Request $request, Coupon $coupon): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($coupon->vendor_id !== $vendor->id) {
            return $this->errorResponse('Coupon not found.', null, 404);
        }

        $coupon->delete();

        return $this->successResponse(null, 'Coupon deleted.');
    }
}
