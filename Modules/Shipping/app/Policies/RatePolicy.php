<?php

namespace Modules\Shipping\Policies;

use App\Models\User;
use Modules\Shipping\Models\ShippingRate;

class RatePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ShippingRate $rate): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shipping.manage') || $user->hasPermissionTo('shipping.rates');
    }

    public function update(User $user, ShippingRate $rate): bool
    {
        return $user->hasPermissionTo('shipping.manage') || $user->hasPermissionTo('shipping.rates');
    }

    public function delete(User $user, ShippingRate $rate): bool
    {
        return $user->hasPermissionTo('shipping.manage');
    }
}
