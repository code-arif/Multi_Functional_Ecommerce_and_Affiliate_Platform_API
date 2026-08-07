<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Models\Category;

class CategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Category $category): bool
    {
        return $category->is_active;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('categories.create') || $user->hasPermissionTo('categories.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.edit') || $user->hasPermissionTo('categories.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('categories.delete') || $user->hasPermissionTo('categories.manage');
    }
}
