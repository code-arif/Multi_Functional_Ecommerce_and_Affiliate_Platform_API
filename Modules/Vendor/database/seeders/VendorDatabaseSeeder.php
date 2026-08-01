<?php

namespace Modules\Vendor\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Vendor\Models\Vendor;

class VendorDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $email = "vendor{$i}@example.com";
            $shopName = "Vendor Shop {$i}";
            $phone = '+880170000000' . $i;

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Vendor {$i}",
                    'phone' => $phone,
                    'password' => bcrypt('password'),
                    'status' => 'active',
                ]
            );

            if (!$user->hasRole('vendor')) {
                $user->assignRole('vendor');
            }

            $vendor = Vendor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'shop_name' => $shopName,
                    'slug' => Str::slug($shopName) . '-' . $i,
                    'email' => $email,
                    'phone' => $phone,
                    'description' => "Demo vendor shop {$i} for development and testing.",
                    'status' => $i % 5 === 0 ? 'pending' : 'active',
                    'commission_rate' => 10,
                    'commission_type' => 'percentage',
                    'wallet_balance' => 1000 + ($i * 100),
                    'total_earned' => 2000 + ($i * 150),
                    'total_withdrawn' => 500 + ($i * 50),
                    'approved_at' => now(),
                ]
            );

            $vendor->profile()->firstOrCreate(
                ['vendor_id' => $vendor->id],
                [
                    'business_type' => 'retail',
                    'business_registration_number' => 'REG-2024-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'website' => "https://vendor{$i}.example.com",
                    'return_policy' => '7 days',
                    'shipping_policy' => 'Free shipping over $50',
                    'is_featured' => $i % 3 === 0,
                ]
            );

            $vendor->addresses()->firstOrCreate(
                ['vendor_id' => $vendor->id, 'is_default' => true],
                [
                    'label' => 'Main Office',
                    'address_line_1' => "{$i} Shop Street",
                    'city' => 'Dhaka',
                    'state' => 'Dhaka',
                    'postal_code' => '120' . ($i % 10),
                    'country' => 'Bangladesh',
                    'is_default' => true,
                ]
            );
        }

        $this->command->info('20 vendor seed records created successfully.');
    }
}
