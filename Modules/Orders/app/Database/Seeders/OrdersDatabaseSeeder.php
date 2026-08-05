<?php

namespace Modules\Orders\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;

class OrdersDatabaseSeeder extends Seeder
{
    /**
     * Seed demo orders across users/vendors.
     */
    public function run(): void
    {
        $users = User::limit(10)->get();
        $products = Product::limit(20)->get();
        $vendors = Vendor::active()->limit(5)->get();

        if ($users->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('OrdersDatabaseSeeder: create users and products first.');

            return;
        }

        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

        for ($i = 1; $i <= 20; $i++) {
            $user = $users->random();
            $vendor = $vendors->isNotEmpty() ? $vendors->random() : null;
            $status = $statuses[array_rand($statuses)];

            $order = Order::create([
                'order_number'           => Order::generateOrderNumber(),
                'group_id'               => Order::generateGroupId(),
                'user_id'                => $user->id,
                'vendor_id'              => $vendor?->id,
                'status'                 => $status,
                'subtotal'               => 0,
                'shipping_charge'        => 60,
                'discount_amount'        => 0,
                'coupon_discount'        => 0,
                'tax_amount'             => 0,
                'total_amount'           => 0,
                'payment_method'         => collect(['cod', 'bkash', 'nagad'])->random(),
                'payment_status'         => $status === 'delivered' ? 'paid' : 'pending',
                'shipping_method'        => 'standard',
                'shipping_name'          => $user->name,
                'shipping_phone'         => $user->phone ?? '01700000000',
                'shipping_email'         => $user->email,
                'shipping_address_line1' => 'House 12, Road 5, Dhanmondi',
                'shipping_city'          => 'Dhaka',
                'shipping_country'       => 'Bangladesh',
                'tracking_token'         => Str::random(32),
            ]);

            $subtotal = 0;
            $itemCount = random_int(1, 3);

            foreach ($products->random($itemCount) as $product) {
                $quantity = random_int(1, 3);
                $unitPrice = (float) ($product->sale_price ?? $product->price ?? 100);
                $subtotal += $unitPrice * $quantity;

                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'vendor_id'    => $vendor?->id,
                    'product_name' => $product->name,
                    'product_sku'  => $product->sku,
                    'product_image'=> $product->thumbnail,
                    'unit_price'   => $unitPrice,
                    'quantity'     => $quantity,
                    'subtotal'     => round($unitPrice * $quantity, 2),
                ]);
            }

            $tax = round($subtotal * 0.05, 2);

            $order->update([
                'subtotal'    => round($subtotal, 2),
                'tax_amount'  => $tax,
                'total_amount'=> round($subtotal + 60 + $tax, 2),
            ]);

            if ($status === 'delivered') {
                $order->update([
                    'delivered_at'   => now(),
                    'shipped_at'     => now()->subDays(2),
                    'confirmed_at'   => now()->subDays(4),
                    'paid_at'        => now(),
                ]);
            } elseif ($status === 'shipped') {
                $order->update([
                    'shipped_at'   => now(),
                    'confirmed_at' => now()->subDays(2),
                ]);
            } elseif ($status === 'cancelled') {
                $order->update([
                    'cancelled_at' => now()->subDay(),
                    'cancel_reason'=> 'Customer cancelled the order.',
                ]);
            }
        }

        $this->command?->info('OrdersDatabaseSeeder: 20 demo orders seeded.');
    }
}
