<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Apple',       'website' => 'https://www.apple.com'],
            ['name' => 'Samsung',     'website' => 'https://www.samsung.com'],
            ['name' => 'Sony',        'website' => 'https://www.sony.com'],
            ['name' => 'LG',          'website' => 'https://www.lg.com'],
            ['name' => 'Dell',        'website' => 'https://www.dell.com'],
            ['name' => 'HP',          'website' => 'https://www.hp.com'],
            ['name' => 'Lenovo',      'website' => 'https://www.lenovo.com'],
            ['name' => 'Asus',        'website' => 'https://www.asus.com'],
            ['name' => 'Acer',        'website' => 'https://www.acer.com'],
            ['name' => 'Huawei',      'website' => 'https://www.huawei.com'],
            ['name' => 'Xiaomi',      'website' => 'https://www.mi.com'],
            ['name' => 'Nike',        'website' => 'https://www.nike.com'],
            ['name' => 'Adidas',      'website' => 'https://www.adidas.com'],
            ['name' => 'Puma',        'website' => 'https://www.puma.com'],
            ['name' => 'Zara',        'website' => 'https://www.zara.com'],
            ['name' => 'H&M',         'website' => 'https://www.hm.com'],
            ['name' => 'Uniqlo',      'website' => 'https://www.uniqlo.com'],
            ['name' => 'Rolex',       'website' => 'https://www.rolex.com'],
            ['name' => 'Casio',       'website' => 'https://www.casio.com'],
            ['name' => 'Gucci',       'website' => 'https://www.gucci.com'],
        ];

        foreach ($brands as $index => $brand) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name'        => $brand['name'],
                    'slug'        => Str::slug($brand['name']),
                    'website'     => $brand['website'],
                    'is_active'   => true,
                    'sort_order'  => $index + 1,
                ]
            );
        }
    }
}
