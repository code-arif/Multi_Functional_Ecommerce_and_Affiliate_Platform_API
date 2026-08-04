<?php

namespace Modules\Catalog\Services;

use Modules\Product\Models\Product;;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProductService
{
    public function createProduct(array $data): Product
    {
        if (empty($data['slug'])) {
            $data['slug'] = Product::generateUniqueSlug($data['name']);
        }

        return Product::create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        if (!empty($data['name']) && empty($data['slug'])) {
            $data['slug'] = Product::generateUniqueSlug($data['name'], $product->id);
        }

        $product->update($data);
        return $product->fresh();
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    public function uploadThumbnail(UploadedFile $file): string
    {
        $path = $file->store('products/thumbnails', 'public');
        return $path;
    }

    public function uploadImage(UploadedFile $file): string
    {
        $path = $file->store('products/images', 'public');
        return $path;
    }
}
