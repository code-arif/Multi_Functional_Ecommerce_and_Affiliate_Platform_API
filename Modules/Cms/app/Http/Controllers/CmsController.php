<?php

namespace Modules\Cms\Http\Controllers;

use Modules\Cms\Models\CmsPage;
use Modules\Cms\Services\SeoService;
use Modules\Promotions\Models\Banner;
use Modules\AdminPanel\Models\Setting;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController
{
    use ApiResponse;

    public function __construct(private SeoService $seoService) {}

    public function pages(): JsonResponse
    {
        $pages = CmsPage::published()->orderBy('order')->get(['id', 'title', 'slug', 'excerpt', 'updated_at']);
        return $this->successResponse($pages);
    }

    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::published()->where('slug', $slug)->firstOrFail();
        return $this->successResponse($page);
    }

    public function banners(string $position): JsonResponse
    {
        $banners = Banner::active()->byPosition($position)->orderBy('sort_order')->get();
        return $this->successResponse($banners);
    }

    public function homepage(): JsonResponse
    {
        $seo = $this->seoService->getHomepageMeta();

        return $this->successResponse([
            'seo'         => $seo,
            'hero_banners' => Banner::active()->byPosition('hero')->orderBy('sort_order')->get(),
            'settings'    => config('ecommerce'),
        ]);
    }

    public function settings(): JsonResponse
    {
        $settings = Setting::public()->pluck('value', 'key');
        return $this->successResponse($settings);
    }
}
