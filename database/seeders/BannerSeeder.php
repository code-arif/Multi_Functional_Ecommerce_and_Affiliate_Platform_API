<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::truncate();

        $banners = [
            // hero_slider
            [
                'title'       => 'নতুন সংগ্রহ এসে গেছে',
                'subtitle'    => 'সেরা দামে সেরা পণ্য পাচ্ছেন। আজই অর্ডার করুন।',
                'image'       => 'https://picsum.photos/seed/hero1/800/500',
                'link'        => '/shop',
                'button_text' => 'এখনই কিনুন',
                'position'    => 'hero_slider',
                'sort_order'  => 1,
                'is_active'   => true,
            ],
            [
                'title'       => 'গ্রীষ্মকালীন সেল চলছে',
                'subtitle'    => 'সর্বোচ্চ ৫০% ছাড়ে পণ্য কিনুন।',
                'image'       => 'https://picsum.photos/seed/hero2/800/500',
                'link'        => '/search',
                'button_text' => 'অফার দেখুন',
                'position'    => 'hero_slider',
                'sort_order'  => 2,
                'is_active'   => true,
            ],
            [
                'title'       => 'বিশেষ ঈদ অফার',
                'subtitle'    => 'ঈদ উপলক্ষে বিশেষ ছাড় ও উপহার পাচ্ছেন।',
                'image'       => 'https://picsum.photos/seed/hero3/800/500',
                'link'        => '/shop?featured=1',
                'button_text' => 'বিস্তারিত দেখুন',
                'position'    => 'hero_slider',
                'sort_order'  => 3,
                'is_active'   => true,
            ],

            // homepage_middle
            [
                'title'       => 'ফ্রি ডেলিভারি পাচ্ছেন',
                'subtitle'    => '১০০০ টাকার উপরে সারা বাংলাদেশে ফ্রি ডেলিভারি।',
                'image'       => 'https://picsum.photos/seed/mid1/1200/400',
                'link'        => '/shop',
                'button_text' => 'কেনাকাটা শুরু করুন',
                'position'    => 'homepage_middle',
                'sort_order'  => 1,
                'is_active'   => true,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }

        $this->command->info('Banners seeded: 3 hero_slider + 1 homepage_middle');
    }
}
