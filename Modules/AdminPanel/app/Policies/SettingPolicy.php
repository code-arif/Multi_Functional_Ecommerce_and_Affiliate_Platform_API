<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('settings.view'); }
    public function update(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('settings.manage'); }
    public function uploadFile(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('settings.manage'); }
}
