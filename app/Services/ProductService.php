<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function getProducts(array $filters)
    {
        $cacheKey = 'products_' . md5(serialize($filters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($filters) {
            return $this->productRepository->getFilteredProducts($filters);
        });
    }

    public function getBySlug(string $slug): Product
    {
        return Cache::remember("product_{$slug}", now()->addMinutes(30), function () use ($slug) {
            return $this->productRepository->findBySlug($slug);
        });
    }

    public function getFeatured(int $limit = 10)
    {
        return Cache::remember("products_featured_{$limit}", now()->addMinutes(30), function () use ($limit) {
            return Product::with(['images', 'category', 'brand'])
                          ->featured()
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
        });
    }

    public function getNew(int $limit = 10)
    {
        return Cache::remember("products_new_{$limit}", now()->addMinutes(30), function () use ($limit) {
            return Product::with(['images', 'category', 'brand'])
                          ->new()
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
        });
    }

    public function getBestsellers(int $limit = 10)
    {
        return Cache::remember("products_bestsellers_{$limit}", now()->addMinutes(30), function () use ($limit) {
            return Product::with(['images', 'category', 'brand'])
                          ->bestseller()
                          ->orderBy('total_sold', 'desc')
                          ->limit($limit)
                          ->get();
        });
    }

    public function getRelated(Product $product, int $limit = 8)
    {
        return Product::with(['images'])
                      ->where('category_id', $product->category_id)
                      ->where('id', '!=', $product->id)
                      ->active()
                      ->inRandomOrder()
                      ->limit($limit)
                      ->get();
    }

    /**
     * Create product
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepository->create($data);

            // Handle images
            if (!empty($data['images'])) {
                $this->syncImages($product, $data['images']);
            }

            // Handle attributes & variants for variable products
            if ($product->type === 'variable' && !empty($data['attributes'])) {
                $this->syncAttributes($product, $data['attributes']);
            }

            if ($product->type === 'variable' && !empty($data['variants'])) {
                $this->syncVariants($product, $data['variants']);
            }

            $this->clearProductCache();

            Log::channel('admin_actions')->info('Product created', [
                'product_id' => $product->id,
                'name'       => $product->name,
            ]);

            return $product->load(['images', 'variants', 'attributes.values', 'category', 'brand']);
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            // If name changed and slug is being reset
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Product::generateUniqueSlug($data['name'], $product->id);
            }

            $product->update($data);

            if (array_key_exists('images', $data)) {
                $this->syncImages($product, $data['images'] ?? []);
            }

            if ($product->type === 'variable') {
                if (array_key_exists('attributes', $data)) {
                    $this->syncAttributes($product, $data['attributes'] ?? []);
                }
                if (array_key_exists('variants', $data)) {
                    $this->syncVariants($product, $data['variants'] ?? []);
                }
            }

            $this->clearProductCache($product->slug);

            return $product->fresh()->load(['images', 'variants', 'attributes.values', 'category', 'brand']);
        });
    }

    public function deleteProduct(Product $product): void
    {
        $this->clearProductCache($product->slug);
        $product->delete();
        Log::channel('admin_actions')->info('Product deleted', ['product_id' => $product->id]);
    }

    public function uploadThumbnail($file): string
    {
        return $file->store('products/thumbnails', 'public');
    }

    public function uploadImage($file): string
    {
        return $file->store('products/images', 'public');
    }

    // ─── Private Helpers ──────────────────────────────────────────

    private function syncImages(Product $product, array $images): void
    {
        // Delete removed images
        $keepIds = collect($images)->pluck('id')->filter()->toArray();
        $product->images()->whereNotIn('id', $keepIds)->each(function ($img) {
            Storage::disk('public')->delete($img->image_path);
            $img->delete();
        });

        foreach ($images as $index => $imageData) {
            if (isset($imageData['id'])) {
                ProductImage::where('id', $imageData['id'])->update([
                    'is_primary'  => $imageData['is_primary'] ?? false,
                    'sort_order'  => $index,
                    'alt_text'    => $imageData['alt_text'] ?? null,
                ]);
            } else {
                ProductImage::create([
                    'product_id'  => $product->id,
                    'image_path'  => $imageData['path'],
                    'alt_text'    => $imageData['alt_text'] ?? null,
                    'is_primary'  => $imageData['is_primary'] ?? ($index === 0),
                    'sort_order'  => $index,
                ]);
            }
        }
    }

    private function syncAttributes(Product $product, array $attributes): void
    {
        $product->attributes()->delete();

        foreach ($attributes as $index => $attrData) {
            $attribute = ProductAttribute::create([
                'product_id'  => $product->id,
                'name'        => $attrData['name'],
                'sort_order'  => $index,
            ]);

            foreach ($attrData['values'] as $valIndex => $valData) {
                ProductAttributeValue::create([
                    'product_attribute_id' => $attribute->id,
                    'value'                => $valData['value'],
                    'color_code'           => $valData['color_code'] ?? null,
                    'sort_order'           => $valIndex,
                ]);
            }
        }
    }

    private function syncVariants(Product $product, array $variants): void
    {
        $product->variants()->delete();

        foreach ($variants as $variantData) {
            ProductVariant::create([
                'product_id'     => $product->id,
                'sku'            => $variantData['sku'] ?? null,
                'attributes'     => $variantData['attributes'],
                'price'          => $variantData['price'],
                'sale_price'     => $variantData['sale_price'] ?? null,
                'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                'image'          => $variantData['image'] ?? null,
                'is_active'      => $variantData['is_active'] ?? true,
            ]);
        }
    }

    private function clearProductCache(?string $slug = null): void
    {
        if ($slug) {
            Cache::forget("product_{$slug}");
        }
        // Clear all product list caches by pattern
        Cache::flush(); // In production, use tagged cache
    }
}
