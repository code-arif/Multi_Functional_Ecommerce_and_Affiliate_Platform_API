<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Promotions\Models\Coupon;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::withCount('usages')
            ->when($request->search, fn($q) => $q->where('code', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($coupons);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'                => 'required|string|max:50|unique:coupons,code',
            'type'                => 'required|string|in:fixed,percentage',
            'value'               => 'required|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount'    => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:0',
            'usage_per_user'      => 'nullable|integer|min:0',
            'is_active'           => 'boolean',
            'starts_at'           => 'nullable|date',
            'expires_at'          => 'nullable|date|after:starts_at',
            'description'         => 'nullable|string|max:500',
        ]);

        $coupon = Coupon::create($validated);

        return $this->createdResponse($coupon, 'Coupon created.');
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->loadCount('usages');
        return $this->successResponse($coupon);
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $validated = $request->validate([
            'code'                => 'sometimes|string|max:50|unique:coupons,code,' . $coupon->id,
            'type'                => 'sometimes|string|in:fixed,percentage',
            'value'               => 'sometimes|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount'    => 'nullable|numeric|min:0',
            'usage_limit'         => 'nullable|integer|min:0',
            'usage_per_user'      => 'nullable|integer|min:0',
            'is_active'           => 'boolean',
            'starts_at'           => 'nullable|date',
            'expires_at'          => 'nullable|date|after:starts_at',
            'description'         => 'nullable|string|max:500',
        ]);

        $coupon->update($validated);

        return $this->successResponse($coupon->fresh(), 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        return $this->noContentResponse('Coupon deleted.');
    }
}
