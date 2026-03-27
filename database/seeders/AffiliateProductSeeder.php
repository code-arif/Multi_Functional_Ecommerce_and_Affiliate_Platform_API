<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\AffiliateProduct;
use Faker\Factory as Faker;

class AffiliateProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $platforms = ['Amazon', 'Daraz', 'eBay', 'AliExpress', 'Flipkart'];

        for ($i = 1; $i <= 30; $i++) {
            $title = $faker->words(rand(2, 5), true);
            AffiliateProduct::create([
                'category_id'     => rand(1, 10), // make sure your categories exist
                'title'           => ucfirst($title),
                'slug'            => Str::slug($title . '-' . $i),
                'description'     => $faker->paragraphs(rand(1, 3), true),
                'thumbnail'       => $faker->imageUrl(300, 300, 'technics', true),
                'images'          => json_encode([
                    $faker->imageUrl(600, 600, 'technics', true),
                    $faker->imageUrl(600, 600, 'technics', true)
                ]),
                'display_price'   => $faker->randomFloat(2, 10, 1000),
                'affiliate_link'  => $faker->url,
                'source_platform' => $faker->randomElement($platforms),
                'click_count'     => $faker->numberBetween(0, 500),
                'meta_title'      => ucfirst($title) . ' - Buy Now',
                'meta_description' => $faker->sentence(),
                'is_active'       => $faker->boolean(90), // 90% active
            ]);
        }
    }
}
