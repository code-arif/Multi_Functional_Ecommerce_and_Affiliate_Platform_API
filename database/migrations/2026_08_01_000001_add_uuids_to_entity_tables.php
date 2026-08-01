<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Main CRUD entity tables that get a public `uuid` column.
     * `users` already has one (see 0001_01_01_000000_create_users_table).
     */
    private array $tables = [
        // Auth
        'addresses',
        'devices',
        'otp_codes',
        // Catalog
        'categories',
        'brands',
        'products',
        'product_variants',
        'product_images',
        'product_attributes',
        'product_attribute_values',
        'vendor_product_prices',
        // Promotions
        'coupons',
        'coupon_usages',
        'banners',
        'promotions',
        'promotion_usages',
        // Cart
        'carts',
        'cart_items',
        'wishlists',
        'compare_lists',
        'compare_list_items',
        'recent_views',
        // Orders
        'orders',
        'order_items',
        'invoices',
        'cancel_requests',
        'order_status_histories',
        // Reviews
        'reviews',
        'review_helpful_votes',
        // Affiliate
        'affiliate_products',
        'affiliate_clicks',
        'affiliate_conversions',
        'affiliate_earnings',
        // CMS
        'cms_pages',
        'cms_blocks',
        'cms_menus',
        'pages',
        // Payments
        'payments',
        'transactions',
        'refunds',
        'payment_methods',
        // Finance
        'commissions',
        'vendor_payout_requests',
        'vendor_settlements',
        // Shipping
        'couriers',
        'shipping_zones',
        'shipping_rates',
        'shipments',
        'tracking_histories',
        'pickup_requests',
        // Support
        'tickets',
        'ticket_messages',
        'chat_rooms',
        'chat_messages',
        'faq_categories',
        'faqs',
        'disputes',
        'dispute_messages',
        // Inventory
        'warehouses',
        'inventory_logs',
        // Core
        'countries',
        'states',
        'cities',
        'currencies',
        'languages',
        'media',
        'settings',
        'activity_logs',
        // Search
        'search_logs',
        // Vendor
        'vendors',
        'vendor_profiles',
        'vendor_addresses',
        'vendor_bank_accounts',
        'vendor_documents',
        'vendor_staff',
        'vendor_wallet_transactions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            // 1. Add nullable column (needed before backfill).
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->after('id');
            });

            // 2. Backfill existing rows with generated uuids.
            DB::table($table)
                ->orderBy('id')
                ->select('id')
                ->chunkById(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Str::uuid()]);
                    }
                });

            // 3. Lock it down: not null + unique index.
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable(false)->unique()->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("{$table}_uuid_unique");
                $t->dropColumn('uuid');
            });
        }
    }
};
