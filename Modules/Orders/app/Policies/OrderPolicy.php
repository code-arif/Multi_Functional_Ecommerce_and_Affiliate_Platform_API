<?php

namespace Modules\Orders\Policies;

use App\Models\User;
use Modules\Orders\Models\Order;

class OrderPolicy
{
    /**
     * Admin staff / super admin may browse all orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.view');
    }

    /**
     * Admins may view any order; customers only their own.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || $user->hasPermissionTo('orders.view')
            || $order->user_id === $user->id;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.manage');
    }

    public function updateAdminNote(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.manage');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin()
            || $user->hasPermissionTo('orders.cancel')
            || $order->user_id === $user->id;
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('orders.refund');
    }
}
