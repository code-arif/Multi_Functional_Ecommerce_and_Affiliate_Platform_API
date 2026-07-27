<?php

namespace Modules\Orders\Policies;

use Modules\Auth\Models\User;
use Modules\Orders\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            && in_array($order->status, ['pending', 'confirmed']);
    }

    public function requestCancel(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }

    /**
     * Vendor-specific permissions.
     */
    public function viewAsVendor(User $user, Order $order): bool
    {
        return $user->vendor?->id === $order->vendor_id;
    }

    public function updateStatusAsVendor(User $user, Order $order): bool
    {
        return $user->vendor?->id === $order->vendor_id
            && in_array($order->status, ['processing', 'shipped']);
    }
}
