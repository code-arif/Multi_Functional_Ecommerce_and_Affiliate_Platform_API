<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;

class AffiliateProductPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('affiliate.view'); }
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('affiliate.manage'); }
    public function update(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('affiliate.manage'); }
    public function delete(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('affiliate.manage'); }
}
