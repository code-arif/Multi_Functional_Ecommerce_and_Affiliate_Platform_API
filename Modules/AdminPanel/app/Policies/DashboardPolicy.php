<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;

class DashboardPolicy
{
    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
