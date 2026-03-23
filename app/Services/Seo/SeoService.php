<?php

namespace App\Services\Seo;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\CmsPage;
use App\Models\AffiliateProduct;
use App\Models\Setting;

class SeoService
{
    private string $defaultTitle;
    private string $defaultDescription;
    private string $storeName;
    private string $storeUrl;

    public function __construct()
    {
        $this->storeName          = Setting::get('store_name', config('app.name'));
        $this->defaultTitle       = Setting::get('meta_title', $this->storeName);
        $this->defaultDescription = Setting::get('meta_description', '');
        $this->storeUrl           = config('app.url');
    }

    // ─── Product SEO ──────────────────────────────────────────────

    public function forProduct(Product $product): array
    {
        $title       = $product->meta_title
            ?? "{$product->name} - Buy Online at {$this->storeName}";
        $description = $product->meta_description
            ?? strip_tags(substr($product->short_description ?? $product->description ?? '', 0, 160));
        $keywords    = $product->meta_keywords
            ?? implode(', ', array_filter([
                $product->name,
                $product->category?->name,
                $product->brand?->name,
                'buy online',
                'Bangladesh',
            ]));

        return [
            'title'           => $title,
            'description'     => $description,
            'keywords'        => $keywords,
            'canonical'       => $this->storeUrl . "/product/{$product->slug}",
            'og'              => [
                'title'       => $title,
                'description' => $description,
                'image'       => $product->thumbnail_url,
                'url'         => $this->storeUrl . "/product/{$product->slug}",
                'type'        => 'product',
                'price'       => $product->current_price,
                'currency'    => 'BDT',
                'availability' => $product->is_in_stock ? 'instock' : 'outofstock',
            ],
            'twitter'         => [
                'card'        => 'summary_large_image',
                'title'       => $title,
                'description' => $description,
                'image'       => $product->thumbnail_url,
            ],
            'structured_data' => $this->productSchema($product),
        ];
    }

    // ─── Category SEO ─────────────────────────────────────────────

    public function forCategory(Category $category): array
    {
        $title       = $category->meta_title
            ?? "Buy {$category->name} Online | {$this->storeName}";
        $description = $category->meta_description
            ?? "Shop the best {$category->name} products online. Fast delivery in Bangladesh.";

        return [
            'title'       => $title,
            'description' => $description,
            'keywords'    => $category->meta_keywords ?? "{$category->name}, buy {$category->name}, online shopping",
            'canonical'   => $this->storeUrl . "/category/{$category->slug}",
            'og'          => [
                'title'   => $title,
                'image'   => $category->image_url,
                'url'     => $this->storeUrl . "/category/{$category->slug}",
                'type'    => 'website',
            ],
        ];
    }

    // ─── CMS Page SEO ─────────────────────────────────────────────

    public function forPage(CmsPage $page): array
    {
        return [
            'title'       => $page->meta_title ?? "{$page->title} | {$this->storeName}",
            'description' => $page->meta_description ?? $this->defaultDescription,
            'keywords'    => $page->meta_keywords ?? '',
            'canonical'   => $this->storeUrl . "/{$page->slug}",
            'og'          => [
                'title'   => $page->meta_title ?? $page->title,
                'url'     => $this->storeUrl . "/{$page->slug}",
                'type'    => 'website',
            ],
        ];
    }

    // ─── Homepage SEO ─────────────────────────────────────────────

    public function forHomepage(): array
    {
        return [
            'title'       => $this->defaultTitle,
            'description' => $this->defaultDescription,
            'keywords'    => Setting::get('meta_keywords', ''),
            'canonical'   => $this->storeUrl,
            'og'          => [
                'title'   => $this->defaultTitle,
                'image'   => $this->storeUrl . '/og-image.jpg',
                'url'     => $this->storeUrl,
                'type'    => 'website',
            ],
        ];
    }

    // ─── Sitemap Generator ────────────────────────────────────────

    public function generateSitemap(): string
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $xml .= $this->sitemapUrl($this->storeUrl, now()->toDateString(), 'daily', '1.0');

        // Products
        Product::active()->select('slug', 'updated_at')->chunk(200, function ($products) use (&$xml) {
            foreach ($products as $product) {
                $xml .= $this->sitemapUrl(
                    $this->storeUrl . "/product/{$product->slug}",
                    $product->updated_at->toDateString(),
                    'weekly',
                    '0.8'
                );
            }
        });

        // Categories
        Category::active()->select('slug', 'updated_at')->chunk(100, function ($cats) use (&$xml) {
            foreach ($cats as $cat) {
                $xml .= $this->sitemapUrl(
                    $this->storeUrl . "/category/{$cat->slug}",
                    $cat->updated_at->toDateString(),
                    'weekly',
                    '0.7'
                );
            }
        });

        // CMS Pages
        CmsPage::active()->select('slug', 'updated_at')->each(function ($page) use (&$xml) {
            $xml .= $this->sitemapUrl(
                $this->storeUrl . "/{$page->slug}",
                $page->updated_at->toDateString(),
                'monthly',
                '0.5'
            );
        });

        $xml .= '</urlset>';
        return $xml;
    }

    // ─── JSON-LD Structured Data ─────────────────────────────────

    private function productSchema(Product $product): array
    {
        $schema = [
            '@context'    => 'https://schema.org/',
            '@type'       => 'Product',
            'name'        => $product->name,
            'description' => strip_tags($product->description ?? ''),
            'sku'         => $product->sku,
            'image'       => $product->thumbnail_url,
            'brand'       => [
                '@type' => 'Brand',
                'name'  => $product->brand?->name ?? $this->storeName,
            ],
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => $this->storeUrl . "/product/{$product->slug}",
                'priceCurrency' => 'BDT',
                'price'         => $product->current_price,
                'availability'  => $product->is_in_stock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller'        => [
                    '@type' => 'Organization',
                    'name'  => $this->storeName,
                ],
            ],
        ];

        if ($product->total_reviews > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $product->average_rating,
                'reviewCount' => $product->total_reviews,
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }

        return $schema;
    }

    private function sitemapUrl(string $url, string $lastMod, string $changeFreq, string $priority): string
    {
        return "  <url>\n"
            . "    <loc>{$url}</loc>\n"
            . "    <lastmod>{$lastMod}</lastmod>\n"
            . "    <changefreq>{$changeFreq}</changefreq>\n"
            . "    <priority>{$priority}</priority>\n"
            . "  </url>\n";
    }
}
