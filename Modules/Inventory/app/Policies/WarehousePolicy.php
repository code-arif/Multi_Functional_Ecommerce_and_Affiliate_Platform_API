<?php

namespace Modules\Inventory\Policies;

use App\Models\User;
use Modules\Inventory\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->vendor?->id === $warehouse->vendor_id
            || $user->hasPermission('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->isVendor() || $user->hasPermission('inventory.manage');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->vendor?->id === $warehouse->vendor_id
            || $user->hasPermission('inventory.manage');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->vendor?->id === $warehouse->vendor_id
            || $user->hasPermission('inventory.manage');
    }
}
