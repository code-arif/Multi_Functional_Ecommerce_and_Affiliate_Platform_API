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
            // ─── Products ─────────────────────────────────────────
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'products.approve', 'products.feature',

            // ─── Categories ───────────────────────────────────────
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'categories.manage',

            // ─── Brands ───────────────────────────────────────────
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
            'brands.manage',

            // ─── Orders ───────────────────────────────────────────
            'orders.view', 'orders.create', 'orders.edit', 'orders.delete',
            'orders.manage', 'orders.cancel', 'orders.refund',

            // ─── Reviews ──────────────────────────────────────────
            'reviews.view', 'reviews.create', 'reviews.edit', 'reviews.delete',
            'reviews.moderate', 'reviews.respond',

            // ─── Users / Customers ────────────────────────────────
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'users.ban', 'users.impersonate',

            // ─── Vendors ──────────────────────────────────────────
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.delete',
            'vendors.approve', 'vendors.manage', 'vendors.suspend',

            // ─── Coupons / Promotions ─────────────────────────────
            'coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete',
            'coupons.manage',

            // ─── Banners ──────────────────────────────────────────
            'banners.view', 'banners.create', 'banners.edit', 'banners.delete',
            'banners.manage',

            // ─── CMS ──────────────────────────────────────────────
            'cms.view', 'cms.create', 'cms.edit', 'cms.delete',
            'cms.manage',

            // ─── Settings ─────────────────────────────────────────
            'settings.view', 'settings.edit',
            'settings.manage',

            // ─── Reports ──────────────────────────────────────────
            'reports.view', 'reports.export', 'reports.sales', 'reports.financial',

            // ─── Affiliate ────────────────────────────────────────
            'affiliate.view', 'affiliate.manage', 'affiliate.analytics',

            // ─── Chat / Support ───────────────────────────────────
            'chat.view', 'chat.send', 'chat.manage',

            // ─── RBAC ─────────────────────────────────────────────
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'roles.manage',
            'permissions.view', 'permissions.create', 'permissions.edit', 'permissions.delete',
            'permissions.manage',

            // ─── Finance ─────────────────────────────────────────
            'finance.view', 'finance.manage', 'finance.commissions',
            'finance.payouts', 'finance.settlements',

            // ─── Shipping ─────────────────────────────────────────
            'shipping.view', 'shipping.manage', 'shipping.zones',
            'shipping.rates', 'shipping.tracking',

            // ─── Notifications ────────────────────────────────────
            'notifications.view', 'notifications.send', 'notifications.manage',

            // ─── Activity Logs ────────────────────────────────────
            'logs.view', 'logs.export', 'logs.clean',

            // ─── Payments ─────────────────────────────────────────
            'payments.view', 'payments.manage', 'payments.refund',

            // ─── Inventory ────────────────────────────────────────
            'inventory.view', 'inventory.manage', 'inventory.adjust',

            // ─── Checkout ─────────────────────────────────────────
            'checkout.view', 'checkout.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->command?->info('Permissions seeded: ' . count($permissions));
    }

    private function createRoles(): void
    {
        // ─── SUPER ADMIN ─────────────────────────────────────────
        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->givePermissionTo(Permission::all());

        // ─── ADMIN STAFF ─────────────────────────────────────────
        $adminStaff = Role::findOrCreate('admin', 'web');
        $adminPermissions = Permission::whereNotIn('name', [
            'roles.manage', 'permissions.manage',
            'settings.manage', 'vendors.approve',
            'users.impersonate', 'logs.clean',
        ])->pluck('name')->toArray();
        $adminStaff->givePermissionTo($adminPermissions);

        // ─── MODERATOR ───────────────────────────────────────────
        $moderator = Role::findOrCreate('moderator', 'web');
        $moderator->givePermissionTo([
            'products.view', 'products.edit', 'products.approve',
            'categories.view',
            'brands.view',
            'orders.view', 'orders.manage',
            'reviews.view', 'reviews.moderate',
            'users.view',
            'chat.view', 'chat.manage',
            'logs.view',
            'inventory.view',
        ]);

        // ─── FINANCE STAFF ───────────────────────────────────────
        $financeStaff = Role::findOrCreate('finance', 'web');
        $financeStaff->givePermissionTo([
            'finance.view', 'finance.manage', 'finance.commissions',
            'finance.payouts', 'finance.settlements',
            'reports.view', 'reports.export', 'reports.financial',
            'orders.view',
            'payments.view', 'payments.manage', 'payments.refund',
            'logs.view',
        ]);

        // ─── SUPPORT STAFF ───────────────────────────────────────
        $supportStaff = Role::findOrCreate('support', 'web');
        $supportStaff->givePermissionTo([
            'orders.view',
            'reviews.view', 'reviews.moderate',
            'users.view',
            'chat.view', 'chat.send', 'chat.manage',
            'cms.view',
            'logs.view',
        ]);

        // ─── VENDOR OWNER ────────────────────────────────────────
        $vendor = Role::findOrCreate('vendor', 'web');
        $vendor->givePermissionTo([
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'orders.view', 'orders.manage',
            'reviews.view', 'reviews.respond',
            'notifications.view',
            'inventory.view', 'inventory.manage',
            'shipping.view', 'shipping.tracking',
            'finance.view', 'finance.payouts',
            'chat.view', 'chat.send',
        ]);

        // ─── VENDOR STAFF ────────────────────────────────────────
        Role::findOrCreate('vendor-staff', 'web');

        // ─── CUSTOMER ─────────────────────────────────────────────
        // Customers have no admin panel permissions;
        // their capabilities are enforced at the application layer.
        Role::findOrCreate('customer', 'web');

        $this->command?->info('Roles seeded: 8');
    }

    private function assignSuperAdmin(): void
    {
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
