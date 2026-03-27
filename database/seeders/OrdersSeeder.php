<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;

class OrdersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $userIds = User::pluck('id')->toArray();
        $productIds = Product::pluck('id')->toArray();

        for ($i = 1; $i <= 20; $i++) {

            // Randomly assign user or guest
            $userId = $faker->optional(0.7, null)->randomElement($userIds);

            $shippingName = $faker->name;
            $shippingPhone = $faker->phoneNumber;
            $shippingEmail = $userId ? User::find($userId)->email : $faker->safeEmail;

            $shippingAddress1 = $faker->streetAddress;
            $shippingCity = $faker->city;
            $shippingCountry = 'Bangladesh';

            // Order-level amounts
            $subtotal = 0;
            $shippingCharge = $faker->randomFloat(2, 0, 50);
            $discountAmount = $faker->randomFloat(2, 0, 20);
            $taxAmount = $faker->randomFloat(2, 0, 30);

            // Create order first to get ID
            $order = Order::create([
                'order_number' => 'ORD-' . date('Y') . '-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'user_id' => $userId,
                'status' => $faker->randomElement(['pending','confirmed','processing','shipped','delivered']),
                'subtotal' => 0, // temp, update later
                'shipping_charge' => $shippingCharge,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => 0, // temp, update later
                'shipping_name' => $shippingName,
                'shipping_phone' => $shippingPhone,
                'shipping_email' => $shippingEmail,
                'shipping_address_line1' => $shippingAddress1,
                'shipping_city' => $shippingCity,
                'shipping_country' => $shippingCountry,
                'payment_method' => $faker->randomElement(['cod','bkash','nagad','sslcommerz','card']),
                'payment_status' => $faker->randomElement(['pending','paid']),
            ]);

            // Create 1-3 order items
            $numItems = $faker->numberBetween(1, 3);
            for ($j = 1; $j <= $numItems; $j++) {
                $productId = $faker->randomElement($productIds);
                $product = Product::find($productId);

                $quantity = $faker->numberBetween(1, 5);
                $unitPrice = $product->price ?? $faker->randomFloat(2, 50, 500);
                $itemSubtotal = $quantity * $unitPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productId,
                    'product_variant_id' => null,
                    'product_name' => $product->name ?? 'Product #' . $productId,
                    'product_sku' => $product->sku ?? 'SKU-' . $productId . '-' . Str::upper(Str::random(4)),
                    'variant_attributes' => null,
                    'product_image' => $product->thumbnail ?? null,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ]);

                $subtotal += $itemSubtotal;
            }

            // Update order totals
            $order->update([
                'subtotal' => $subtotal,
                'total_amount' => $subtotal + $shippingCharge + $taxAmount - $discountAmount,
            ]);
        }
    }
}
