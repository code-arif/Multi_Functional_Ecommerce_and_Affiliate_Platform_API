<?php

namespace Modules\AdminPanel\Policies;

use App\Models\User;
use Modules\Product\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->isModerator(); }
    public function view(User $user, Product $product): bool { return $user->isAdmin() || $user->isModerator(); }
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('products.create'); }
    public function update(User $user, Product $product): bool { return $user->isAdmin() || $user->hasPermissionTo('products.edit'); }
    public function delete(User $user, Product $product): bool { return $user->isAdmin() || $user->hasPermissionTo('products.delete'); }
    public function uploadImage(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('products.create'); }
}
