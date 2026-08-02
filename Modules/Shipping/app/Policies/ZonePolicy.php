<?php

namespace Modules\Shipping\Policies;

use App\Models\User;
use Modules\Shipping\Models\ShippingZone;

class ZonePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ShippingZone $zone): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shipping.manage') || $user->hasPermissionTo('shipping.zones');
    }

    public function update(User $user, ShippingZone $zone): bool
    {
        return $user->hasPermissionTo('shipping.manage') || $user->hasPermissionTo('shipping.zones');
    }

    public function delete(User $user, ShippingZone $zone): bool
    {
        return $user->hasPermissionTo('shipping.manage');
    }
}
