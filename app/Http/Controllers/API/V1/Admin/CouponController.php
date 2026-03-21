<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coupons = Coupon::withCount('usages')
            ->when($request->search, fn($q) => $q->where('code', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return $this->paginatedResponse($coupons);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code'                  => 'required|string|unique:coupons,code',
            'type'                  => 'required|in:percentage,fixed',
            'value'                 => 'required|numeric|min:0',
            'minimum_order_amount'  => 'nullable|numeric|min:0',
            'maximum_discount'      => 'nullable|numeric|min:0',
            'usage_limit'           => 'nullable|integer|min:1',
            'usage_limit_per_user'  => 'nullable|integer|min:1',
            'is_active'             => 'boolean',
            'starts_at'             => 'nullable|date',
            'expires_at'            => 'nullable|date|after:starts_at',
            'description'           => 'nullable|string',
        ]);
        $coupon = Coupon::create(array_merge($request->validated(), ['code' => strtoupper($request->code)]));
        return $this->createdResponse($coupon, 'Coupon created.');
    }

    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($request->except('code'));
        return $this->successResponse($coupon, 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $coupon->delete();
        return $this->noContentResponse('Coupon deleted.');
    }
}
