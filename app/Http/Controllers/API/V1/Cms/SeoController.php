<?php

namespace App\Http\Controllers\API\V1\Cms;

use App\Http\Controllers\API\V1\BaseController;
use App\Models\Product;
use App\Models\Category;
use App\Models\CmsPage;
use App\Models\AffiliateProduct;
use App\Services\Seo\SeoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends BaseController
{
    public function __construct(private SeoService $seoService) {}

    /**
     * GET /api/v1/seo/product/{slug}
     */
    public function product(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->active()->with(['category', 'brand'])->firstOrFail();
        return $this->successResponse($this->seoService->forProduct($product));
    }

    /**
     * GET /api/v1/seo/category/{slug}
     */
    public function category(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->active()->firstOrFail();
        return $this->successResponse($this->seoService->forCategory($category));
    }

    /**
     * GET /api/v1/seo/page/{slug}
     */
    public function page(string $slug): JsonResponse
    {
        $page = CmsPage::where('slug', $slug)->active()->firstOrFail();
        return $this->successResponse($this->seoService->forPage($page));
    }

    /**
     * GET /api/v1/seo/homepage
     */
    public function homepage(): JsonResponse
    {
        return $this->successResponse($this->seoService->forHomepage());
    }

    /**
     * GET /sitemap.xml — served as XML (add this to web.php for direct access)
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap_xml', now()->addHours(6), function () {
            return $this->seoService->generateSitemap();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
