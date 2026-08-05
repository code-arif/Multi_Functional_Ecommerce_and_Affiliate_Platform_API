<?php

namespace Modules\Inventory\Policies;

use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVendor() || $user->hasPermission('inventory.view');
    }

    public function viewLogs(User $user): bool
    {
        return $user->isVendor() || $user->hasPermission('inventory.view');
    }

    public function adjust(User $user): bool
    {
        return $user->isVendor() || $user->hasPermission('inventory.manage');
    }

    public function viewSummary(User $user): bool
    {
        return $user->isVendor() || $user->hasPermission('inventory.view');
    }
}
