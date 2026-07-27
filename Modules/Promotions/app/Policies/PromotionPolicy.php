<?php

namespace Modules\Promotions\Policies;

use Modules\Auth\Models\User;
use Modules\Promotions\Models\Promotion;

class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('promotions.view');
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('promotions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('promotions.create');
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('promotions.edit');
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('promotions.delete');
    }

    public function toggle(User $user, Promotion $promotion): bool
    {
        return $user->hasPermission('promotions.manage');
    }

    public function analytics(User $user): bool
    {
        return $user->hasPermission('promotions.view') || $user->isAdmin();
    }
}
