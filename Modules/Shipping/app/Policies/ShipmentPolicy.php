<?php

namespace Modules\Shipping\Policies;

use Modules\Auth\Models\User;
use Modules\Shipping\Models\Shipment;

class ShipmentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipping.view')
            || $user->vendor?->id === $shipment->vendor_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shipping.manage')
            || $user->isVendor();
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipping.manage')
            || $user->vendor?->id === $shipment->vendor_id;
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->hasPermissionTo('shipping.manage');
    }
}
