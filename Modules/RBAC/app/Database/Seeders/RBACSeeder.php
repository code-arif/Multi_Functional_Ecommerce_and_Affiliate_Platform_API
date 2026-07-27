<?php

namespace Modules\RBAC\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\Auth\Models\User;

class RBACSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();
        $this->assignSuperAdmin();
    }

    private function createPermissions(): void
    {
        $permissions = [
            // Products
            'products.view', 'products.create', 'products.edit', 'products.delete',
            // Categories
            'categories.view', 'categories.manage',
            // Brands
            'brands.view', 'brands.manage',
            // Orders
            'orders.view', 'orders.manage',
            // Reviews
            'reviews.view', 'reviews.moderate',
            // Users
            'users.view', 'users.create', 'users.edit', 'users.ban',
            // Vendors
            'vendors.view', 'vendors.approve', 'vendors.manage',
            // Coupons
            'coupons.manage',
            // Banners
            'banners.view', 'banners.manage',
            // CMS
            'cms.manage',
            // Settings
            'settings.view', 'settings.manage',
            // Reports
            'reports.view',
            // Affiliates
            'affiliate.manage',
            // Chat
            'chat.manage',
            // RBAC
            'roles.view', 'roles.manage', 'permissions.view', 'permissions.manage',
            // Finance
            'finance.view', 'finance.manage',
            // Shipping
            'shipping.view', 'shipping.manage',
            // Notifications
            'notifications.view', 'notifications.send',
            // Activity Logs
            'logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->command?->info('Permissions seeded: ' . count($permissions));
    }

    private function createRoles(): void
    {
        // ─── Super Admin ──────────────────────────────────────────
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        // ─── Admin Staff ──────────────────────────────────────────
        $adminStaff = Role::findOrCreate('admin', 'web');
        // Admin staff get most permissions except super-admin-only ones
        $adminPermissions = Permission::whereNotIn('name', [
            'roles.manage', 'permissions.manage', 'settings.manage', 'vendors.approve',
        ])->get();
        $adminStaff->givePermissionTo($adminPermissions);

        // ─── Moderator ────────────────────────────────────────────
        $moderator = Role::findOrCreate('moderator', 'web');
        $moderator->givePermissionTo([
            'products.view', 'products.edit',
            'categories.view',
            'brands.view',
            'orders.view', 'orders.manage',
            'reviews.view', 'reviews.moderate',
            'users.view',
            'chat.manage',
            'logs.view',
        ]);

        // ─── Vendor Owner ─────────────────────────────────────────
        $vendor = Role::findOrCreate('vendor', 'web');
        $vendor->givePermissionTo([
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'orders.view',
            'reviews.view',
            'notifications.view',
        ]);

        // ─── Vendor Staff ─────────────────────────────────────────
        Role::findOrCreate('vendor-staff', 'web');

        // ─── Customer ─────────────────────────────────────────────
        // Customers have no admin permissions; handled by the application
        Role::findOrCreate('customer', 'web');

        $this->command?->info('Roles seeded: 6');
    }

    private function assignSuperAdmin(): void
    {
        // Create admin user if not exists
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'     => 'Super Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
                'status'   => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
            $this->command?->info("Super-admin role assigned to {$admin->email}");
        }
    }
}
