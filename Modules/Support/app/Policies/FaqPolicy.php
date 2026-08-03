<?php

namespace Modules\Support\Policies;

use App\Models\User;

class FaqPolicy
{
    public function viewAny(User $user): bool { return true; } // Public
    public function create(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('support.faqs.manage'); }
    public function update(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('support.faqs.manage'); }
    public function delete(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('support.faqs.manage'); }
}
