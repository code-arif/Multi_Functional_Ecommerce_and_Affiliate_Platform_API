<?php

namespace Modules\AdminPanel\Policies;

use Modules\Auth\Models\User;
use Modules\Orders\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('orders.view'); }
    public function view(User $user, Order $order): bool { return $user->isAdmin() || $user->hasPermissionTo('orders.view'); }
    public function updateStatus(User $user, Order $order): bool { return $user->isAdmin() || $user->hasPermissionTo('orders.manage'); }
    public function updateAdminNote(User $user, Order $order): bool { return $user->isAdmin() || $user->hasPermissionTo('orders.manage'); }
}
