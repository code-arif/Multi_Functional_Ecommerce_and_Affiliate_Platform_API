<?php

namespace Modules\Checkout\Policies;

use Modules\Auth\Models\User;

class CheckoutPolicy
{
    /**
     * Determine whether the user can checkout.
     * Must be authenticated and not banned.
     */
    public function process(User $user): bool
    {
        return $user->status === 'active';
    }

    /**
     * Preview order before placing.
     */
    public function preview(User $user): bool
    {
        return $user->status === 'active';
    }
}
