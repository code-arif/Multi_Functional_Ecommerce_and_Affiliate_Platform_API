<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Product\Models\Product;
use Modules\Vendor\Models\Vendor;

class CatalogDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $electronics = Category::firstOrCreate(
            ['slug' => 'electronics'],
            [
                'name'        => 'Electronics',
                'description' => 'Electronic devices and accessories',
                'is_active'   => true,
                'is_featured' => true,
                'sort_order'  => 1,
            ]
        );

        $clothing = Category::firstOrCreate(
            ['slug' => 'clothing'],
            [
                'name'        => 'Clothing',
                'description' => 'Apparel and fashion items',
                'is_active'   => true,
                'is_featured' => true,
                'sort_order'  => 2,
            ]
        );

        // Subcategories
        Category::firstOrCreate(
            ['slug' => 'mobile-phones'],
            [
                'parent_id'   => $electronics->id,
                'name'        => 'Mobile Phones',
                'description' => 'Smartphones and accessories',
                'is_active'   => true,
                'sort_order'  => 1,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'laptops'],
            [
                'parent_id'   => $electronics->id,
                'name'        => 'Laptops',
                'description' => 'Notebooks and laptops',
                'is_active'   => true,
                'sort_order'  => 2,
            ]
        );

        Category::firstOrCreate(
            ['slug' => 'mens-fashion'],
            [
                'parent_id'   => $clothing->id,
                'name'        => 'Men\'s Fashion',
                'description' => 'Men\'s clothing and accessories',
                'is_active'   => true,
                'sort_order'  => 1,
            ]
        );

        // Brands
        $apple = Brand::firstOrCreate(
            ['slug' => 'apple'],
            [
                'name'        => 'Apple',
                'description' => 'Apple Inc. - Premium electronics',
                'website'     => 'https://apple.com',
                'is_active'   => true,
            ]
        );

        $samsung = Brand::firstOrCreate(
            ['slug' => 'samsung'],
            [
                'name'        => 'Samsung',
                'description' => 'Samsung Electronics',
                'website'     => 'https://samsung.com',
                'is_active'   => true,
            ]
        );

        Brand::firstOrCreate(
            ['slug' => 'nike'],
            [
                'name'        => 'Nike',
                'description' => 'Nike - Sportswear and footwear',
                'website'     => 'https://nike.com',
                'is_active'   => true,
            ]
        );

        // Products
        $phoneCat = Category::where('slug', 'mobile-phones')->first();
        $laptopCat = Category::where('slug', 'laptops')->first();
        $mensCat = Category::where('slug', 'mens-fashion')->first();

        $iphone = Product::firstOrCreate(
            ['slug' => 'iphone-15-pro'],
            [
                'category_id'    => $phoneCat?->id,
                'brand_id'       => $apple->id,
                'name'           => 'iPhone 15 Pro',
                'sku'            => 'IP15P-256',
                'type'           => 'variable',
                'price'          => 1199.00,
                'sale_price'     => 1099.00,
                'cost_price'     => 800.00,
                'stock_quantity' => 50,
                'stock_status'   => 'in_stock',
                'short_description' => 'The latest iPhone with A17 Pro chip',
                'description'    => 'The iPhone 15 Pro features a strong and lightweight titanium design, the A17 Pro chip, and a powerful camera system.',
                'average_rating' => 4.8,
                'total_reviews'  => 245,
                'total_sold'     => 1500,
                'is_featured'    => true,
                'is_new'         => true,
                'is_bestseller'  => true,
                'status'         => 'active',
                'published_at'   => now(),
                'meta_title'     => 'iPhone 15 Pro - Apple',
                'meta_description' => 'Buy iPhone 15 Pro with A17 Pro chip',
            ]
        );

        $galaxy = Product::firstOrCreate(
            ['slug' => 'samsung-galaxy-s24'],
            [
                'category_id'    => $phoneCat?->id,
                'brand_id'       => $samsung->id,
                'name'           => 'Samsung Galaxy S24',
                'sku'            => 'SGS24-256',
                'type'           => 'simple',
                'price'          => 999.00,
                'sale_price'     => 899.00,
                'stock_quantity' => 100,
                'stock_status'   => 'in_stock',
                'short_description' => 'Samsung Galaxy S24 with AI features',
                'description'    => 'The Galaxy S24 comes with Galaxy AI, a powerful processor, and an amazing camera.',
                'average_rating' => 4.6,
                'total_reviews'  => 189,
                'total_sold'     => 1200,
                'is_featured'    => true,
                'status'         => 'active',
                'published_at'   => now(),
            ]
        );

        // ── Vendor-Product Pricing (if vendors exist) ──────────────
        $vendor = Vendor::first();
        if ($vendor) {
            $iphone->vendorProductPrices()->firstOrCreate(
                ['vendor_id' => $vendor->id, 'product_id' => $iphone->id],
                [
                    'price'         => 1199.00,
                    'sale_price'    => 1099.00,
                    'stock_quantity' => 50,
                    'is_active'     => true,
                ]
            );

            $galaxy->vendorProductPrices()->firstOrCreate(
                ['vendor_id' => $vendor->id, 'product_id' => $galaxy->id],
                [
                    'price'         => 999.00,
                    'sale_price'    => 899.00,
                    'stock_quantity' => 100,
                    'is_active'     => true,
                ]
            );
        }

        $this->command->info('Catalog seed data created successfully.');
    }
}
