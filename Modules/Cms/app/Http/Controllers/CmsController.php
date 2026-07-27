<?php

namespace Modules\Cms\Http\Controllers;

use Modules\Cms\Models\CmsPage;
use Modules\Cms\Services\CmsService;
use Modules\Cms\Services\SeoService;
use Modules\Cms\Http\Resources\CmsPageResource;
use Modules\Cms\Http\Resources\CmsBlockResource;
use Modules\Cms\Http\Resources\CmsMenuResource;
use Modules\Promotions\Models\Banner;
use Modules\AdminPanel\Models\Setting;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController
{
    use ApiResponse;

    public function __construct(
        private CmsService $cmsService,
        private SeoService $seoService
    ) {}

    public function pages(): JsonResponse
    {
        $pages = $this->cmsService->getPublishedPages();
        return $this->successResponse(CmsPageResource::collection($pages));
    }

    public function page(string $slug): JsonResponse
    {
        $page = $this->cmsService->getPageBySlug($slug);
        return $this->successResponse(new CmsPageResource($page));
    }

    public function blocks(): JsonResponse
    {
        $blocks = $this->cmsService->getActiveBlocks();
        return $this->successResponse(CmsBlockResource::collection($blocks));
    }

    public function block(string $slug): JsonResponse
    {
        $block = $this->cmsService->getBlockBySlug($slug);
        if (!$block) return $this->errorResponse('Block not found.', null, 404);
        return $this->successResponse(new CmsBlockResource($block));
    }

    public function menus(): JsonResponse
    {
        $menus = $this->cmsService->getAllMenus();
        return $this->successResponse(CmsMenuResource::collection($menus));
    }

    public function menu(string $location): JsonResponse
    {
        $menu = $this->cmsService->getActiveMenuByLocation($location);
        if (!$menu) return $this->errorResponse('Menu not found.', null, 404);
        return $this->successResponse(new CmsMenuResource($menu));
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
            'seo'          => $seo,
            'hero_banners' => Banner::active()->byPosition('hero')->orderBy('sort_order')->get(),
            'settings'     => config('ecommerce'),
        ]);
    }

    public function settings(): JsonResponse
    {
        $settings = Setting::public()->pluck('value', 'key');
        return $this->successResponse($settings);
    }
}
