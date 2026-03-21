<?php

namespace App\Http\Controllers\API\V1\Cms;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Models\CmsPage;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CmsController extends Controller
{
    /**
     * GET /api/v1/cms/pages
     */
    public function pages(): JsonResponse
    {
        $pages = CmsPage::active()->select('id', 'title', 'slug', 'sort_order')->orderBy('sort_order')->get();
        return $this->successResponse($pages);
    }

    /**
     * GET /api/v1/cms/pages/{slug}
     */
    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::active()->where('slug', $slug)->firstOrFail();
        return $this->successResponse([
            'id'               => $page->id,
            'title'            => $page->title,
            'slug'             => $page->slug,
            'content'          => $page->content,
            'meta_title'       => $page->meta_title ?? $page->title,
            'meta_description' => $page->meta_description,
            'meta_keywords'    => $page->meta_keywords,
        ]);
    }

    /**
     * GET /api/v1/cms/banners/{position}
     */
    public function banners(string $position): JsonResponse
    {
        $cacheKey = "banners_{$position}";
        $banners  = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($position) {
            return Banner::active()->forPosition($position)->get();
        });

        return $this->successResponse(BannerResource::collection($banners));
    }

    /**
     * GET /api/v1/cms/homepage — All homepage data in one request
     */
    public function homepage(): JsonResponse
    {
        $data = Cache::remember('homepage_data', now()->addMinutes(30), function () {
            return [
                'sliders'     => BannerResource::collection(Banner::active()->forPosition('hero_slider')->get()),
                'top_banners' => BannerResource::collection(Banner::active()->forPosition('homepage_top')->get()),
                'mid_banners' => BannerResource::collection(Banner::active()->forPosition('homepage_middle')->get()),
                'settings'    => [
                    'store_name'     => Setting::get('store_name'),
                    'currency_symbol'=> Setting::get('currency_symbol', '৳'),
                    'phone'          => Setting::get('store_phone'),
                ],
            ];
        });

        return $this->successResponse($data, 'Homepage data fetched.');
    }

    /**
     * GET /api/v1/cms/settings — Public settings
     */
    public function settings(): JsonResponse
    {
        $settings = Cache::remember('public_settings', now()->addDay(), function () {
            return [
                'store_name'        => Setting::get('store_name'),
                'store_email'       => Setting::get('store_email'),
                'store_phone'       => Setting::get('store_phone'),
                'store_address'     => Setting::get('store_address'),
                'currency'          => Setting::get('currency', 'BDT'),
                'currency_symbol'   => Setting::get('currency_symbol', '৳'),
                'logo'              => Setting::get('store_logo'),
                'favicon'           => Setting::get('store_favicon'),
                'free_shipping_over'=> Setting::get('free_shipping_over', '1000'),
                'shipping_charge'   => Setting::get('shipping_charge', '60'),
                'facebook_url'      => Setting::get('facebook_url'),
                'instagram_url'     => Setting::get('instagram_url'),
                'twitter_url'       => Setting::get('twitter_url'),
                'youtube_url'       => Setting::get('youtube_url'),
                'meta_title'        => Setting::get('meta_title'),
                'meta_description'  => Setting::get('meta_description'),
            ];
        });

        return $this->successResponse($settings);
    }
}
