<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;

class CmsPagePolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('cms.view'); }
    public function view(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('cms.view'); }
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('cms.manage'); }
    public function update(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('cms.manage'); }
    public function delete(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('cms.manage'); }
}
