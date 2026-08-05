<?php

namespace Modules\AdminPanel\Policies;

use App\Models\User;
use Modules\Promotions\Models\Banner;

class BannerPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('banners.view'); }
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('banners.manage'); }
    public function update(User $user, Banner $banner): bool { return $user->isAdmin() || $user->hasPermissionTo('banners.manage'); }
    public function delete(User $user, Banner $banner): bool { return $user->isAdmin() || $user->hasPermissionTo('banners.manage'); }
}
