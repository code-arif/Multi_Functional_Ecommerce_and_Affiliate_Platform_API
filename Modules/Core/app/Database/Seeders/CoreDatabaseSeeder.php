<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoreDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCountries();
        $this->seedCurrencies();
        $this->seedLanguages();
    }

    private function seedCountries(): void
    {
        if (DB::table('countries')->count() > 0) return;

        DB::table('countries')->insert([
            [
                'uuid'            => (string) Str::uuid(),
                'name'            => 'Bangladesh',
                'iso2'            => 'BD',
                'iso3'            => 'BGD',
                'phone_code'      => '+880',
                'currency_code'   => 'BDT',
                'currency_symbol' => '৳',
                'is_active'       => true,
                'is_default'      => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'uuid'            => (string) Str::uuid(),
                'name'            => 'United States',
                'iso2'            => 'US',
                'iso3'            => 'USA',
                'phone_code'      => '+1',
                'currency_code'   => 'USD',
                'currency_symbol' => '$',
                'is_active'       => true,
                'is_default'      => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'uuid'            => (string) Str::uuid(),
                'name'            => 'India',
                'iso2'            => 'IN',
                'iso3'            => 'IND',
                'phone_code'      => '+91',
                'currency_code'   => 'INR',
                'currency_symbol' => '₹',
                'is_active'       => true,
                'is_default'      => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }

    private function seedCurrencies(): void
    {
        if (DB::table('currencies')->count() > 0) return;

        DB::table('currencies')->insert([
            [
                'uuid'          => (string) Str::uuid(),
                'name'          => 'Bangladeshi Taka',
                'code'          => 'BDT',
                'symbol'        => '৳',
                'exchange_rate' => 1.00000000,
                'precision'     => 2,
                'is_default'    => true,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'uuid'          => (string) Str::uuid(),
                'name'          => 'US Dollar',
                'code'          => 'USD',
                'symbol'        => '$',
                'exchange_rate' => 0.00900000,
                'precision'     => 2,
                'is_default'    => false,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'uuid'          => (string) Str::uuid(),
                'name'          => 'Indian Rupee',
                'code'          => 'INR',
                'symbol'        => '₹',
                'exchange_rate' => 0.75000000,
                'precision'     => 2,
                'is_default'    => false,
                'is_active'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }

    private function seedLanguages(): void
    {
        if (DB::table('languages')->count() > 0) return;

        DB::table('languages')->insert([
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'English',
                'code'       => 'en',
                'direction'  => 'ltr',
                'is_default' => true,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'বাংলা',
                'code'       => 'bn',
                'direction'  => 'ltr',
                'is_default' => false,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
