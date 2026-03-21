<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'store_name',        'value' => 'EcoShop',          'group' => 'general', 'type' => 'text'],
            ['key' => 'store_email',        'value' => 'info@ecoshop.com', 'group' => 'general', 'type' => 'text'],
            ['key' => 'store_phone',        'value' => '+8801XXXXXXXXX',   'group' => 'general', 'type' => 'text'],
            ['key' => 'store_address',      'value' => 'Dhaka, Bangladesh','group' => 'general', 'type' => 'text'],
            ['key' => 'store_logo',         'value' => null,               'group' => 'general', 'type' => 'file'],
            ['key' => 'store_favicon',      'value' => null,               'group' => 'general', 'type' => 'file'],
            ['key' => 'currency',           'value' => 'BDT',              'group' => 'general', 'type' => 'text'],
            ['key' => 'currency_symbol',    'value' => '৳',                'group' => 'general', 'type' => 'text'],

            // Shipping
            ['key' => 'shipping_charge',    'value' => '60',               'group' => 'shipping','type' => 'text'],
            ['key' => 'free_shipping_over', 'value' => '1000',             'group' => 'shipping','type' => 'text'],

            // SEO
            ['key' => 'meta_title',         'value' => 'EcoShop - Best Online Store', 'group' => 'seo', 'type' => 'text'],
            ['key' => 'meta_description',   'value' => 'Shop the best products',      'group' => 'seo', 'type' => 'text'],
            ['key' => 'meta_keywords',      'value' => 'ecommerce, shop, online',     'group' => 'seo', 'type' => 'text'],

            // Social
            ['key' => 'facebook_url',  'value' => null, 'group' => 'social', 'type' => 'text'],
            ['key' => 'instagram_url', 'value' => null, 'group' => 'social', 'type' => 'text'],
            ['key' => 'twitter_url',   'value' => null, 'group' => 'social', 'type' => 'text'],
            ['key' => 'youtube_url',   'value' => null, 'group' => 'social', 'type' => 'text'],

            // Maintenance
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'general', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
