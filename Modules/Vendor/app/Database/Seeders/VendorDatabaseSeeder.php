<?php

namespace Modules\Vendor\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Vendor\Models\Vendor;
use Modules\Auth\Models\User;

class VendorDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create a demo vendor user
        $user = User::firstOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name'     => 'Demo Vendor',
                'phone'    => '+8801700000001',
                'password' => bcrypt('password'),
                'status'   => 'active',
            ]
        );

        // Create vendor
        $vendor = Vendor::firstOrCreate(
            ['user_id' => $user->id],
            [
                'shop_name'       => 'Demo Shop',
                'slug'            => 'demo-shop',
                'email'           => 'vendor@example.com',
                'phone'           => '+8801700000001',
                'description'     => 'A demo vendor shop for development and testing.',
                'status'          => 'active',
                'commission_rate' => 10,
                'commission_type' => 'percentage',
                'wallet_balance'  => 5000,
                'total_earned'    => 15000,
                'total_withdrawn' => 10000,
                'approved_at'     => now(),
            ]
        );

        // Create profile
        $vendor->profile()->firstOrCreate(
            ['vendor_id' => $vendor->id],
            [
                'business_type'                => 'retail',
                'business_registration_number' => 'REG-2024-001',
                'website'                      => 'https://demoshop.com',
                'return_policy'                => '7 days',
                'shipping_policy'              => 'Free shipping over $50',
                'is_featured'                  => true,
            ]
        );

        // Create address
        $vendor->addresses()->firstOrCreate(
            ['vendor_id' => $vendor->id, 'is_default' => true],
            [
                'label'          => 'Main Office',
                'address_line_1' => '123 Shop Street',
                'city'           => 'Dhaka',
                'state'          => 'Dhaka',
                'postal_code'    => '1205',
                'country'        => 'Bangladesh',
                'is_default'     => true,
            ]
        );

        // Create a second test vendor (pending approval)
        $user2 = User::firstOrCreate(
            ['email' => 'vendor2@example.com'],
            [
                'name'     => 'Pending Vendor',
                'phone'    => '+8801700000002',
                'password' => bcrypt('password'),
                'status'   => 'active',
            ]
        );

        Vendor::firstOrCreate(
            ['user_id' => $user2->id],
            [
                'shop_name'       => 'Pending Shop',
                'slug'            => 'pending-shop',
                'email'           => 'vendor2@example.com',
                'description'     => 'Awaiting approval.',
                'status'          => 'pending',
                'commission_rate' => 10,
                'commission_type' => 'percentage',
            ]
        );

        $this->command->info('Vendor seed data created successfully.');
    }
}
