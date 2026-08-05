<?php

namespace Modules\Checkout\Events;

use Modules\Cart\Models\Cart;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckoutProcessed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Cart $cart,
        public array $orders,
        public User $user
    ) {}
}
