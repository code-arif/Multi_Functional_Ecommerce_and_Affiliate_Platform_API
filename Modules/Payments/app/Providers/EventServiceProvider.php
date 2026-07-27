<?php

namespace Modules\Payments\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Payments\Models\Payment;
use Modules\Payments\Policies\PaymentPolicy;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Future: PaymentCompleted::class => [SendPaymentConfirmation::class],
    ];

    protected $policies = [
        Payment::class => PaymentPolicy::class,
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
