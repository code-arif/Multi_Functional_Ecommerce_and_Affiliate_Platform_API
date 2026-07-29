<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;
use Modules\Catalog\Models\Brand;

class BrandPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->isModerator(); }
    public function view(User $user, Brand $brand): bool { return $user->isAdmin() || $user->isModerator(); }
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('brands.manage'); }
    public function update(User $user, Brand $brand): bool { return $user->isAdmin() || $user->hasPermissionTo('brands.manage'); }
    public function delete(User $user, Brand $brand): bool { return $user->isAdmin() || $user->hasPermissionTo('brands.manage'); }
}
