<?php

namespace Modules\Orders\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Orders\Models\Order;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 500, 5000);

        return [
            // Random unique number — Laravel pre-materializes all models for count(n)->create(),
            // so a DB-sequenced number would collide within a single batch.
            'order_number'          => 'ORD-' . now()->format('Y') . '-' . str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            'group_id'              => Order::generateGroupId(),
            'user_id'               => null,
            'vendor_id'             => null,
            'status'                => 'pending',
            'subtotal'              => $subtotal,
            'shipping_charge'       => 60,
            'discount_amount'       => 0,
            'coupon_discount'       => 0,
            'tax_amount'            => round($subtotal * 0.05, 2),
            'total_amount'          => round($subtotal + 60 + ($subtotal * 0.05), 2),
            'coupon_code'           => null,
            'payment_method'        => $this->faker->randomElement(['cod', 'bkash', 'nagad', 'sslcommerz', 'card']),
            'payment_status'        => 'pending',
            'shipping_method'       => 'standard',
            'shipping_name'         => $this->faker->name(),
            'shipping_phone'        => '017' . $this->faker->numerify('########'),
            'shipping_email'        => $this->faker->safeEmail(),
            'shipping_address_line1'=> $this->faker->streetAddress(),
            'shipping_address_line2'=> null,
            'shipping_city'         => $this->faker->city(),
            'shipping_state'        => null,
            'shipping_postal_code'  => $this->faker->postcode(),
            'shipping_country'      => 'Bangladesh',
            'tracking_token'        => Str::random(32),
        ];
    }

    public function forStatus(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
