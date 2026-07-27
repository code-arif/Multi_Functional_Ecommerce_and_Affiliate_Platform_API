<?php

namespace Modules\Vendor\Database\Factories;

use Modules\Vendor\Models\Vendor;
use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        $shopName = $this->faker->company();

        return [
            'user_id'         => User::factory(),
            'shop_name'       => $shopName,
            'slug'            => Str::slug($shopName) . '-' . $this->faker->unique()->randomNumber(5),
            'email'           => $this->faker->companyEmail(),
            'phone'           => $this->faker->phoneNumber(),
            'description'     => $this->faker->paragraph(),
            'status'          => 'active',
            'commission_rate' => 10,
            'commission_type' => 'percentage',
            'wallet_balance'  => 0,
            'total_earned'    => 0,
            'total_withdrawn' => 0,
            'approved_at'     => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'      => 'pending',
            'approved_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
