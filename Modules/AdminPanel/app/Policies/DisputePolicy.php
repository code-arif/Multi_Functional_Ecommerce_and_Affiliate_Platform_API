<?php

namespace Modules\AdminPanel\Policies;

use App\Models\User;

class DisputePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.manage');
    }

    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.manage');
    }

    public function updateStatus(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.manage');
    }

    public function addMessage(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.manage');
    }
}
