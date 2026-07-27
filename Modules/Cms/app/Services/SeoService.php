<?php

namespace Modules\Cms\Services;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Category;
use Modules\Cms\Models\CmsPage;

class SeoService
{
    public function getHomepageMeta(): array
    {
        $settings = app()->make(\Modules\AdminPanel\Models\Setting::class);

        return [
            'title'       => $settings::getValue('seo_home_title', config('app.name')),
            'description' => $settings::getValue('seo_home_description', ''),
            'keywords'    => $settings::getValue('seo_home_keywords', ''),
        ];
    }

    public function getProductMeta(string $slug): array
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return $this->getDefaultMeta();
        }

        return [
            'title'       => $product->meta_title ?: $product->name,
            'description' => $product->meta_description ?: $product->short_description,
            'keywords'    => $product->meta_keywords ?: '',
            'image'       => $product->thumbnail_url,
            'type'        => 'product',
        ];
    }

    public function getCategoryMeta(string $slug): array
    {
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return $this->getDefaultMeta();
        }

        return [
            'title'       => $category->meta_title ?: $category->name,
            'description' => $category->meta_description ?: $category->description,
            'keywords'    => $category->meta_keywords ?: '',
            'image'       => $category->image_url,
            'type'        => 'category',
        ];
    }

    public function getPageMeta(string $slug): array
    {
        $page = CmsPage::published()->where('slug', $slug)->first();

        if (!$page) {
            return $this->getDefaultMeta();
        }

        return [
            'title'       => $page->meta_title ?: $page->title,
            'description' => $page->meta_description ?: $page->excerpt,
            'keywords'    => $page->meta_keywords ?: '',
            'image'       => $page->og_image ? asset('storage/' . $page->og_image) : null,
            'type'        => 'page',
        ];
    }

    public function getDefaultMeta(): array
    {
        return [
            'title'       => config('app.name'),
            'description' => config('app.name') . ' - Multi-vendor marketplace',
            'keywords'    => '',
            'type'        => 'website',
        ];
    }

    public function generateSitemap(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $xml .= $this->urlTag(url('/'), now()->toIso8601String(), '1.0', 'daily');

        // Categories
        foreach (Category::active()->get() as $category) {
            $xml .= $this->urlTag(
                url("/category/{$category->slug}"),
                $category->updated_at->toIso8601String(),
                '0.8',
                'weekly'
            );
        }

        // Products
        foreach (Product::active()->get() as $product) {
            $xml .= $this->urlTag(
                url("/product/{$product->slug}"),
                $product->updated_at->toIso8601String(),
                '0.9',
                'weekly'
            );
        }

        // CMS Pages
        foreach (CmsPage::published()->get() as $page) {
            $xml .= $this->urlTag(
                url("/page/{$page->slug}"),
                $page->updated_at->toIso8601String(),
                '0.7',
                'monthly'
            );
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function urlTag(string $loc, string $lastmod, string $priority, string $changefreq): string
    {
        return "  <url>\n" .
            "    <loc>{$loc}</loc>\n" .
            "    <lastmod>{$lastmod}</lastmod>\n" .
            "    <changefreq>{$changefreq}</changefreq>\n" .
            "    <priority>{$priority}</priority>\n" .
            "  </url>\n";
    }
}
