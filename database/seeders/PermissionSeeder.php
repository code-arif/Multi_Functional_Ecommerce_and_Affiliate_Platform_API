<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Permission structure:
     *   group.action
     *
     * Groups: products, orders, users, categories, brands,
     *         coupons, reviews, cms, banners, settings,
     *         affiliate, reports
     */
    private array $permissions = [
        // ─── Products ──────────────────────────────────────────
        ['name' => 'products.view',    'display_name' => 'View Products',    'group' => 'products'],
        ['name' => 'products.create',  'display_name' => 'Create Products',  'group' => 'products'],
        ['name' => 'products.edit',    'display_name' => 'Edit Products',    'group' => 'products'],
        ['name' => 'products.delete',  'display_name' => 'Delete Products',  'group' => 'products'],

        // ─── Orders ────────────────────────────────────────────
        ['name' => 'orders.view',      'display_name' => 'View Orders',      'group' => 'orders'],
        ['name' => 'orders.manage',    'display_name' => 'Manage Orders',    'group' => 'orders'],
        ['name' => 'orders.cancel',    'display_name' => 'Cancel Orders',    'group' => 'orders'],
        ['name' => 'orders.refund',    'display_name' => 'Refund Orders',    'group' => 'orders'],

        // ─── Users ─────────────────────────────────────────────
        ['name' => 'users.view',       'display_name' => 'View Users',       'group' => 'users'],
        ['name' => 'users.manage',     'display_name' => 'Manage Users',     'group' => 'users'],
        ['name' => 'users.ban',        'display_name' => 'Ban/Unban Users',  'group' => 'users'],

        // ─── Categories ────────────────────────────────────────
        ['name' => 'categories.view',  'display_name' => 'View Categories',  'group' => 'categories'],
        ['name' => 'categories.manage', 'display_name' => 'Manage Categories', 'group' => 'categories'],

        // ─── Brands ────────────────────────────────────────────
        ['name' => 'brands.view',      'display_name' => 'View Brands',      'group' => 'brands'],
        ['name' => 'brands.manage',    'display_name' => 'Manage Brands',    'group' => 'brands'],

        // ─── Coupons ───────────────────────────────────────────
        ['name' => 'coupons.view',     'display_name' => 'View Coupons',     'group' => 'coupons'],
        ['name' => 'coupons.manage',   'display_name' => 'Manage Coupons',   'group' => 'coupons'],

        // ─── Reviews ───────────────────────────────────────────
        ['name' => 'reviews.view',     'display_name' => 'View Reviews',     'group' => 'reviews'],
        ['name' => 'reviews.moderate', 'display_name' => 'Moderate Reviews', 'group' => 'reviews'],

        // ─── CMS ───────────────────────────────────────────────
        ['name' => 'cms.view',         'display_name' => 'View CMS Pages',   'group' => 'cms'],
        ['name' => 'cms.manage',       'display_name' => 'Manage CMS Pages', 'group' => 'cms'],

        // ─── Banners ───────────────────────────────────────────
        ['name' => 'banners.view',     'display_name' => 'View Banners',     'group' => 'banners'],
        ['name' => 'banners.manage',   'display_name' => 'Manage Banners',   'group' => 'banners'],

        // ─── Settings ──────────────────────────────────────────
        ['name' => 'settings.view',    'display_name' => 'View Settings',    'group' => 'settings'],
        ['name' => 'settings.manage',  'display_name' => 'Manage Settings',  'group' => 'settings'],

        // ─── Affiliate ─────────────────────────────────────────
        ['name' => 'affiliate.view',   'display_name' => 'View Affiliate',   'group' => 'affiliate'],
        ['name' => 'affiliate.manage', 'display_name' => 'Manage Affiliate', 'group' => 'affiliate'],

        // ─── Reports ───────────────────────────────────────────
        ['name' => 'reports.view',     'display_name' => 'View Reports',     'group' => 'reports'],

        // ─── Chat ──────────────────────────────────────────────
        ['name' => 'chat.view',        'display_name' => 'View Chat',        'group' => 'chat'],
        ['name' => 'chat.manage',      'display_name' => 'Manage Chat',      'group' => 'chat'],
    ];

    public function run(): void
    {
        // Create all permissions
        foreach ($this->permissions as $permData) {
            Permission::firstOrCreate(['name' => $permData['name']], $permData);
        }

        // Assign ALL permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::pluck('id')->toArray();
            $adminRole->permissions()->sync($allPermissions);
        }

        // Assign LIMITED permissions to moderator role
        $moderatorRole = Role::where('name', 'moderator')->first();
        if ($moderatorRole) {
            $moderatorPermissions = Permission::whereIn('name', [
                'products.view',
                'orders.view',
                'orders.manage',
                'users.view',
                'categories.view',
                'brands.view',
                'reviews.view',
                'reviews.moderate',
                'cms.view',
                'reports.view',
                'chat.view',
                'chat.manage',
            ])->pluck('id')->toArray();

            $moderatorRole->permissions()->sync($moderatorPermissions);
        }
    }
}
