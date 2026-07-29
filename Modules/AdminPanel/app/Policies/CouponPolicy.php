<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;
use Modules\Promotions\Models\Coupon;

class CouponPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('coupons.view'); }
    public function view(User $user, Coupon $coupon): bool { return $user->isAdmin() || $user->hasPermissionTo('coupons.view'); }
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('coupons.manage'); }
    public function update(User $user, Coupon $coupon): bool { return $user->isAdmin() || $user->hasPermissionTo('coupons.manage'); }
    public function delete(User $user, Coupon $coupon): bool { return $user->isAdmin() || $user->hasPermissionTo('coupons.manage'); }
}
