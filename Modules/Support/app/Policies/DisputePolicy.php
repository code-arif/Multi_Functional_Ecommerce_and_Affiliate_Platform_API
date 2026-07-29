<?php

namespace Modules\Support\Policies;

use Modules\Auth\Models\User;
use Modules\AdminPanel\Models\Dispute;

class DisputePolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('support.disputes.view'); }
    public function view(User $user, Dispute $dispute): bool { return $user->isAdmin() || $user->id === $dispute->customer_id; }
    public function create(User $user): bool { return true; }
}
