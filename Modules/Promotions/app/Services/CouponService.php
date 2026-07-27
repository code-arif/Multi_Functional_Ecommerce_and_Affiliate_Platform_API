<?php

namespace Modules\Promotions\Services;

use Modules\Promotions\Models\Coupon;
use Modules\Promotions\Models\CouponUsage;
use Modules\Auth\Models\User;

class CouponService
{
    public function applyCoupon(string $code, User $user, float $orderTotal): array
    {
        $coupon = Coupon::active()->where('code', $code)->first();

        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid or expired coupon code.'];
        }

        if ($coupon->minimum_order_amount && $orderTotal < $coupon->minimum_order_amount) {
            return ['success' => false, 'message' => "Minimum order amount of {$coupon->minimum_order_amount} required."];
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return ['success' => false, 'message' => 'This coupon has reached its usage limit.'];
        }

        if ($coupon->usage_per_user) {
            $userUsage = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();
            if ($userUsage >= $coupon->usage_per_user) {
                return ['success' => false, 'message' => 'You have already used this coupon.'];
            }
        }

        $discount = match ($coupon->type) {
            'fixed'    => min($coupon->value, $orderTotal),
            'percentage' => min($orderTotal * ($coupon->value / 100), $coupon->maximum_discount ?? $orderTotal),
            default    => 0,
        };

        return [
            'success'  => true,
            'coupon'   => $coupon,
            'discount' => round($discount, 2),
        ];
    }

    public function recordUsage(Coupon $coupon, User $user, int $orderId, float $discountAmount): void
    {
        CouponUsage::create([
            'coupon_id'       => $coupon->id,
            'user_id'         => $user->id,
            'order_id'        => $orderId,
            'discount_amount' => $discountAmount,
        ]);

        $coupon->increment('used_count');
    }
}
