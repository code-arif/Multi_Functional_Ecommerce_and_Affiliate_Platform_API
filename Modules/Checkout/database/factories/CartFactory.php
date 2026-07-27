<?php

namespace Modules\Checkout\Database\Factories;

use Modules\Cart\Models\Cart;
use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'session_id' => Str::random(40),
        ];
    }
}
