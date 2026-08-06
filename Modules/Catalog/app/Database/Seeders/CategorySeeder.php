<?php

namespace Modules\Catalog\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Root Categories
        $electronics = Category::create([
            'name' => 'Electronics',
            'description' => 'Explore the latest in tech, gadgets, smart home appliances, and personal computing.',
            'icon' => 'device-laptop',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $fashion = Category::create([
            'name' => 'Fashion & Apparel',
            'description' => 'Find trend-setting clothing, footwear, and accessories for men, women, and kids.',
            'icon' => 'shirt',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $home = Category::create([
            'name' => 'Home & Kitchen',
            'description' => 'Everything you need for a comfortable home, including furniture, cookware, and decor.',
            'icon' => 'home',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $health = Category::create([
            'name' => 'Health & Beauty',
            'description' => 'Premium skincare, makeup, wellness products, and personal care essentials.',
            'icon' => 'sparkles',
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        $sports = Category::create([
            'name' => 'Sports & Outdoors',
            'description' => 'Gear up for adventure with athletic apparel, fitness equipment, and camping supplies.',
            'icon' => 'run',
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        // 2. Sub-categories
        Category::create([
            'parent_id' => $electronics->id,
            'name' => 'Laptops & Computers',
            'description' => 'High-performance laptops, desktop builds, monitors, and computing parts.',
            'icon' => 'device-desktop',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Category::create([
            'parent_id' => $electronics->id,
            'name' => 'Smartphones & Tablets',
            'description' => 'The latest smartphones, tablets, smart watches, and cellular accessories.',
            'icon' => 'device-mobile',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Category::create([
            'parent_id' => $fashion->id,
            'name' => "Men's Wear",
            'description' => "Stylish shirts, suits, activewear, jeans, and men's fashion essentials.",
            'icon' => 'man',
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Category::create([
            'parent_id' => $fashion->id,
            'name' => "Women's Wear",
            'description' => "Dresses, tops, bags, jewelry, and women's contemporary styling.",
            'icon' => 'woman',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Category::create([
            'parent_id' => $home->id,
            'name' => 'Kitchen Appliances',
            'description' => 'Air fryers, blenders, coffee makers, microwaves, and dining tools.',
            'icon' => 'tools-kitchen-2',
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
