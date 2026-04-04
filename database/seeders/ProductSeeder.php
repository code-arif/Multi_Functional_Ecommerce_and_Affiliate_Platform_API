<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {

            $isVariable = $i % 3 === 0; // every 3rd product is variable

            $product = Product::create([
                'category_id' => rand(1, 5),
                'brand_id' => rand(1, 5),
                'name' => "Product {$i}",
                'slug' => Str::slug("Product {$i}"),
                'sku' => "SKU-" . strtoupper(Str::random(6)),
                'type' => $isVariable ? 'variable' : 'simple',
                'price' => rand(500, 5000),
                'sale_price' => rand(300, 4500),
                'cost_price' => rand(200, 3000),
                'stock_quantity' => rand(5, 50),
                'short_description' => "Short description for product {$i}",
                'description' => "Full description for product {$i}",
                'thumbnail' => "products/sample{$i}.jpg",
                'is_featured' => rand(0, 1),
                'is_new' => rand(0, 1),
                'is_bestseller' => rand(0, 1),
                'status' => 'active',
                'published_at' => now(),
            ]);

            // Images (3 per product)
            for ($img = 1; $img <= 3; $img++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => "products/sample{$img}.jpg",
                    'is_primary' => $img === 1,
                    'sort_order' => $img
                ]);
            }

            // Variable product logic
            if ($isVariable) {

                // Attribute: Size
                $sizeAttr = ProductAttribute::create([
                    'product_id' => $product->id,
                    'name' => 'Size',
                ]);

                $sizes = ['S', 'M', 'L'];

                $sizeValues = [];
                foreach ($sizes as $index => $size) {
                    $val = ProductAttributeValue::create([
                        'product_attribute_id' => $sizeAttr->id,
                        'value' => $size,
                        'sort_order' => $index
                    ]);
                    $sizeValues[] = $val;
                }

                // Attribute: Color
                $colorAttr = ProductAttribute::create([
                    'product_id' => $product->id,
                    'name' => 'Color',
                ]);

                $colors = [
                    ['name' => 'Red', 'code' => '#FF0000'],
                    ['name' => 'Blue', 'code' => '#0000FF'],
                    ['name' => 'Black', 'code' => '#000000'],
                ];

                $colorValues = [];
                foreach ($colors as $index => $color) {
                    $val = ProductAttributeValue::create([
                        'product_attribute_id' => $colorAttr->id,
                        'value' => $color['name'],
                        'color_code' => $color['code'],
                        'sort_order' => $index
                    ]);
                    $colorValues[] = $val;
                }

                // Variants (Size × Color)
                foreach ($sizeValues as $size) {
                    foreach ($colorValues as $color) {

                        ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => "VAR-" . strtoupper(Str::random(8)),
                            'attributes' => json_encode([
                                'Size' => $size->value,
                                'Color' => $color->value,
                            ]),
                            'price' => rand(800, 6000),
                            'sale_price' => rand(500, 5500),
                            'stock_quantity' => rand(1, 20),
                            'image' => "products/variant.jpg",
                            'is_active' => true
                        ]);
                    }
                }
            }
        }
    }
}
