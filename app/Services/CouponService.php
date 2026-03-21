<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponService
{
    public function validate(string $code, float $subtotal, ?int $userId): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (!$coupon) {
            throw new \Exception('Coupon code is invalid.');
        }

        if (!$coupon->is_active) {
            throw new \Exception('This coupon is no longer active.');
        }

        if ($coupon->is_expired) {
            throw new \Exception('This coupon has expired.');
        }

        if ($coupon->is_usage_limit_reached) {
            throw new \Exception('This coupon has reached its usage limit.');
        }

        if ($subtotal < $coupon->minimum_order_amount) {
            throw new \Exception(
                "Minimum order amount of {$coupon->minimum_order_amount} required for this coupon."
            );
        }

        if ($userId && !$coupon->isValidForUser($userId)) {
            throw new \Exception('You have already used this coupon.');
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'coupon'   => $coupon,
            'discount' => $discount,
        ];
    }

    public function recordUsage(Coupon $coupon, ?int $userId, int $orderId, float $discountAmount): void
    {
        CouponUsage::create([
            'coupon_id'       => $coupon->id,
            'user_id'         => $userId,
            'order_id'        => $orderId,
            'discount_amount' => $discountAmount,
        ]);

        $coupon->increment('used_count');
    }
}
