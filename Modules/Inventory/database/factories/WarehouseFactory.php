<?php

namespace Modules\Inventory\Database\Factories;

use Modules\Inventory\Models\Warehouse;
use Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $name = $this->faker->company() . ' Warehouse';

        return [
            'vendor_id'      => Vendor::factory(),
            'name'           => $name,
            'slug'           => Str::slug($name) . '-' . $this->faker->unique()->randomNumber(5),
            'address_line_1' => $this->faker->streetAddress(),
            'city'           => $this->faker->city(),
            'state'          => $this->faker->state(),
            'country'        => $this->faker->country(),
            'contact_name'   => $this->faker->name(),
            'contact_phone'  => $this->faker->phoneNumber(),
            'is_active'      => true,
            'is_default'     => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }
}
