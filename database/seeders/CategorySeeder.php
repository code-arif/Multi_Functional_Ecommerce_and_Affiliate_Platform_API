<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [

            // 🧑‍💻 Electronics
            [
                'name' => 'Electronics',
                'children' => [
                    'Mobile Phones',
                    'Laptops',
                    'Tablets',
                    'Cameras',
                    'Accessories'
                ]
            ],

            // 👕 Fashion
            [
                'name' => 'Fashion',
                'children' => [
                    'Men Clothing',
                    'Women Clothing',
                    'Kids Wear',
                    'Shoes',
                    'Watches'
                ]
            ],

            // 🏠 Home & Living
            [
                'name' => 'Home & Living',
                'children' => [
                    'Furniture',
                    'Home Decor',
                    'Kitchen Appliances',
                    'Lighting',
                    'Bedding'
                ]
            ],

            // 🛒 Grocery
            [
                'name' => 'Grocery',
                'children' => [
                    'Fruits',
                    'Vegetables',
                    'Beverages',
                    'Snacks',
                    'Dairy Products'
                ]
            ],

            // 💄 Beauty & Care
            [
                'name' => 'Beauty & Care',
                'children' => [
                    'Skincare',
                    'Haircare',
                    'Makeup',
                    'Fragrance',
                    'Personal Care'
                ]
            ],

            // ⚽ Sports
            [
                'name' => 'Sports & Outdoors',
                'children' => [
                    'Fitness Equipment',
                    'Outdoor Gear',
                    'Sportswear',
                    'Cycling',
                    'Camping'
                ]
            ],

            // 📚 Books
            [
                'name' => 'Books & Stationery',
                'children' => [
                    'Academic Books',
                    'Novels',
                    'Office Supplies',
                    'Art Supplies',
                    'Magazines'
                ]
            ],

            // 🧸 Toys
            [
                'name' => 'Toys & Baby',
                'children' => [
                    'Toys',
                    'Baby Care',
                    'Diapers',
                    'Baby Clothing',
                    'Strollers'
                ]
            ],

            // 🚗 Automotive
            [
                'name' => 'Automotive',
                'children' => [
                    'Car Accessories',
                    'Motorbike Parts',
                    'Oils & Fluids',
                    'Tools',
                    'Tyres'
                ]
            ],

            // 🐾 Pets
            [
                'name' => 'Pet Supplies',
                'children' => [
                    'Pet Food',
                    'Pet Toys',
                    'Pet Grooming',
                    'Pet Beds',
                    'Aquariums'
                ]
            ],

        ];

        foreach ($categories as $parent) {

            $parentCategory = Category::create([
                'name' => $parent['name'],
                'slug' => Str::slug($parent['name']),
                'parent_id' => null,
                'is_active' => true,
            ]);

            foreach ($parent['children'] as $child) {
                Category::create([
                    'name' => $child,
                    'slug' => Str::slug($child),
                    'parent_id' => $parentCategory->id,
                    'is_active' => true,
                ]);
            }
        }
    }
}
