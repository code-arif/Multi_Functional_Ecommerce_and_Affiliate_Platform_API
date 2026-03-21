<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // 🔐 User Management
            ['name' => 'user.view',    'display_name' => 'View Users',    'group' => 'user'],
            ['name' => 'user.create',  'display_name' => 'Create User',   'group' => 'user'],
            ['name' => 'user.update',  'display_name' => 'Update User',   'group' => 'user'],
            ['name' => 'user.delete',  'display_name' => 'Delete User',   'group' => 'user'],

            // 🛍️ Product Management
            ['name' => 'product.view',    'display_name' => 'View Product',    'group' => 'product'],
            ['name' => 'product.create',  'display_name' => 'Create Product',  'group' => 'product'],
            ['name' => 'product.update',  'display_name' => 'Update Product',  'group' => 'product'],
            ['name' => 'product.delete',  'display_name' => 'Delete Product',  'group' => 'product'],

            // 📦 Category Management
            ['name' => 'category.view',    'display_name' => 'View Category',    'group' => 'category'],
            ['name' => 'category.create',  'display_name' => 'Create Category',  'group' => 'category'],
            ['name' => 'category.update',  'display_name' => 'Update Category',  'group' => 'category'],
            ['name' => 'category.delete',  'display_name' => 'Delete Category',  'group' => 'category'],

            // 🧾 Order Management
            ['name' => 'order.view',    'display_name' => 'View Orders',    'group' => 'order'],
            ['name' => 'order.create',  'display_name' => 'Create Order',   'group' => 'order'],
            ['name' => 'order.update',  'display_name' => 'Update Order',   'group' => 'order'],
            ['name' => 'order.delete',  'display_name' => 'Delete Order',   'group' => 'order'],

            // 📊 Report Management
            ['name' => 'report.view', 'display_name' => 'View Reports', 'group' => 'report'],

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
