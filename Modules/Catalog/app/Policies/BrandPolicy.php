<?php

namespace Modules\Catalog\Policies;

use Modules\Auth\Models\User;
use Modules\Catalog\Models\Brand;

class BrandPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Brand $brand): bool
    {
        return $brand->is_active;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('brands.manage');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->hasPermission('brands.manage');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->hasPermission('brands.manage');
    }
}
