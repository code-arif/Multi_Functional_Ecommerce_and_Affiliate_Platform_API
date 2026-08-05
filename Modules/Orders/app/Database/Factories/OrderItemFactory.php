<?php

namespace Modules\Orders\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice = $this->faker->randomFloat(2, 50, 1000);
        $quantity = $this->faker->numberBetween(1, 5);

        return [
            'order_id'         => Order::factory(),
            'product_id'       => null,
            'product_variant_id' => null,
            'vendor_id'        => null,
            'product_name'     => $this->faker->words(3, true),
            'product_sku'      => 'SKU-' . strtoupper($this->faker->lexify('????')),
            'variant_attributes' => null,
            'product_image'    => null,
            'unit_price'       => $unitPrice,
            'quantity'         => $quantity,
            'subtotal'         => round($unitPrice * $quantity, 2),
        ];
    }
}
