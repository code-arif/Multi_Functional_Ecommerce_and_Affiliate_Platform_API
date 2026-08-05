<?php

namespace Modules\AdminPanel\Policies;

use App\Models\User;
use Modules\Catalog\Models\Category;

class CategoryPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->isModerator(); }
    public function view(User $user, Category $category): bool { return $user->isAdmin() || $user->isModerator(); }
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('categories.manage'); }
    public function update(User $user, Category $category): bool { return $user->isAdmin() || $user->hasPermissionTo('categories.manage'); }
    public function delete(User $user, Category $category): bool { return $user->isAdmin() || $user->hasPermissionTo('categories.manage'); }
}
