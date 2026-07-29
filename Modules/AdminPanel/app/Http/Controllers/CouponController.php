<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\AdminPanel\Http\Resources\CouponResource;
use Modules\AdminPanel\Http\Requests\StoreCouponRequest;
use Modules\AdminPanel\Http\Requests\UpdateCouponRequest;
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

        return $this->paginatedResponse(CouponResource::collection($coupons));
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::create($request->validated());
        return $this->createdResponse(new CouponResource($coupon), 'Coupon created.');
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->loadCount('usages');
        return $this->successResponse(new CouponResource($coupon));
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($request->validated());
        return $this->successResponse(new CouponResource($coupon->fresh()), 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        return $this->noContentResponse('Coupon deleted.');
    }
}
