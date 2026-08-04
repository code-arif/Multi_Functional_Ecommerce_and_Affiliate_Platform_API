<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RBAC\Database\Seeders\RBACSeeder;
use Modules\Vendor\Database\Seeders\VendorDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Core module: countries, currencies, languages
            // \Modules\Core\Database\Seeders\CoreDatabaseSeeder::class,
            // RBAC: 39 permissions, 6 roles + admin user
            RBACSeeder::class,
            // Other seeders
            // SettingSeeder::class,
            // CategorySeeder::class,
            // BrandSeeder::class,
            // BannerSeeder::class,
            // AffiliateProductSeeder::class,
            // ProductSeeder::class,
            // Vendor: demo vendor account with shop
            // VendorDatabaseSeeder::class,
        ]);
    }
}
