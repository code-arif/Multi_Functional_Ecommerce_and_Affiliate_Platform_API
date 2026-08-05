<?php

namespace Modules\AdminPanel\Policies;

use App\Models\User;

class ReportPolicy
{
    public function view(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('reports.view'); }
    public function viewSales(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('reports.sales'); }
    public function viewFinancial(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('reports.financial'); }
}
