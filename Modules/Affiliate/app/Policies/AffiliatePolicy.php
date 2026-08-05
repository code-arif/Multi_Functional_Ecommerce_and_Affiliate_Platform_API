<?php

namespace Modules\Affiliate\Policies;

use App\Models\User;

class AffiliatePolicy
{
    public function viewDashboard(User $user): bool
    {
        return true; // Any authenticated user can view their affiliate dashboard
    }

    public function viewAnalytics(User $user): bool
    {
        return $user->hasPermission('affiliate.analytics') || $user->isAdmin();
    }

    public function manageConversions(User $user): bool
    {
        return $user->hasPermission('affiliate.manage') || $user->isAdmin();
    }

    public function manageEarnings(User $user): bool
    {
        return $user->hasPermission('affiliate.manage') || $user->isAdmin();
    }
}
