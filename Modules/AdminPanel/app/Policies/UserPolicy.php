<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('users.view'); }
    public function view(User $user, User $targetUser): bool { return $user->isAdmin() || $user->hasPermissionTo('users.view'); }
    public function updateStatus(User $user, User $targetUser): bool { return $user->isAdmin() || $user->hasPermissionTo('users.ban'); }
}
