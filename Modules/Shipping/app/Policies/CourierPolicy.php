<?php

namespace Modules\Shipping\Policies;

use App\Models\User;
use Modules\Shipping\Models\Courier;

class CourierPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Courier $courier): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shipping.manage');
    }

    public function update(User $user, Courier $courier): bool
    {
        return $user->hasPermissionTo('shipping.manage');
    }

    public function delete(User $user, Courier $courier): bool
    {
        return $user->hasPermissionTo('shipping.manage');
    }
}
