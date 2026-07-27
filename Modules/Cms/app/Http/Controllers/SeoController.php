<?php

namespace Modules\Cms\Http\Controllers;

use Modules\Cms\Services\SeoService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoController
{
    use ApiResponse;

    public function __construct(private SeoService $seoService) {}

    public function homepage(): JsonResponse
    {
        return $this->successResponse($this->seoService->getHomepageMeta());
    }

    public function product(string $slug): JsonResponse
    {
        return $this->successResponse($this->seoService->getProductMeta($slug));
    }

    public function category(string $slug): JsonResponse
    {
        return $this->successResponse($this->seoService->getCategoryMeta($slug));
    }

    public function page(string $slug): JsonResponse
    {
        return $this->successResponse($this->seoService->getPageMeta($slug));
    }

    public function sitemap(): \Illuminate\Http\Response
    {
        $xml = $this->seoService->generateSitemap();
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
