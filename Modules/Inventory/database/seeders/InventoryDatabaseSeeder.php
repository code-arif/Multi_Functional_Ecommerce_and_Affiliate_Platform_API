<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Inventory\Models\Warehouse;
use Modules\Vendor\Models\Vendor;

class InventoryDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = Vendor::first();

        if (!$vendor) {
            $this->command->warn('No vendors found. Skipping inventory seed.');
            return;
        }

        // Create default warehouse
        Warehouse::firstOrCreate(
            ['vendor_id' => $vendor->id, 'slug' => 'main-warehouse-' . $vendor->id],
            [
                'name'           => 'Main Warehouse',
                'address_line_1' => '123 Warehouse Blvd',
                'city'           => 'Dhaka',
                'state'          => 'Dhaka',
                'country'        => 'Bangladesh',
                'contact_name'   => 'Warehouse Manager',
                'contact_phone'  => '+8801700000100',
                'is_active'      => true,
                'is_default'     => true,
            ]
        );

        // Create secondary warehouse
        Warehouse::firstOrCreate(
            ['vendor_id' => $vendor->id, 'slug' => 'secondary-warehouse-' . $vendor->id],
            [
                'name'           => 'Secondary Warehouse',
                'address_line_1' => '456 Storage Avenue',
                'city'           => 'Chittagong',
                'state'          => 'Chittagong',
                'country'        => 'Bangladesh',
                'contact_name'   => 'Storage Lead',
                'contact_phone'  => '+8801700000101',
                'is_active'      => true,
                'is_default'     => false,
            ]
        );

        $this->command->info('Inventory seed data created successfully.');
    }
}
