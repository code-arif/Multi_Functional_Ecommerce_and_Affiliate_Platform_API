<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
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
        return $user->hasPermissionTo('brands.create') || $user->hasPermissionTo('brands.manage');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->hasPermissionTo('brands.edit') || $user->hasPermissionTo('brands.manage');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->hasPermissionTo('brands.delete') || $user->hasPermissionTo('brands.manage');
    }
}
