<?php

namespace Modules\Payments\Policies;

use Modules\Auth\Models\User;
use Modules\Payments\Models\Payment;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->order->user_id;
    }

    public function process(User $user): bool
    {
        return $user->status === 'active';
    }

    public function refund(User $user): bool
    {
        return $user->hasPermission('payments.refund') || $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payments.view') || $user->isAdmin();
    }
}
