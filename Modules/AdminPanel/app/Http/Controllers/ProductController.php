<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Catalog\Http\Resources\ProductListResource;
use Modules\Catalog\Http\Requests\StoreProductRequest;
use Modules\Catalog\Http\Requests\UpdateProductRequest;
use Modules\Catalog\Services\ProductService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController
{
    use ApiResponse;

    public function __construct(private ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'brand', 'variants'])
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->category_uuid, fn($q) => $q->where('category_id', \Modules\Catalog\Models\Category::findByUuid($request->category_uuid)?->id))
            ->withCount('reviews')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(ProductListResource::collection($products));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $this->mapProductUuids($request->validated());

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->productService->uploadThumbnail($request->file('thumbnail'));
        }

        $product = $this->productService->createProduct($data);

        return $this->createdResponse(new ProductResource($product), 'Product created successfully.');
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['images', 'variants', 'attributes.values', 'category', 'brand', 'reviews.user']);
        return $this->successResponse(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $this->mapProductUuids($request->validated());

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->productService->uploadThumbnail($request->file('thumbnail'));
        }

        $product = $this->productService->updateProduct($product, $data);

        return $this->successResponse(new ProductResource($product), 'Product updated successfully.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->deleteProduct($product);
        return $this->noContentResponse('Product deleted.');
    }

    /**
     * Map public uuid references (category/brand) to internal foreign keys.
     */
    private function mapProductUuids(array $data): array
    {
        if (!empty($data['category_uuid'])) {
            $data['category_id'] = \Modules\Catalog\Models\Category::findByUuidOrFail($data['category_uuid'])->id;
        }
        if (!empty($data['brand_uuid'])) {
            $data['brand_id'] = \Modules\Catalog\Models\Brand::findByUuidOrFail($data['brand_uuid'])->id;
        }
        unset($data['category_uuid'], $data['brand_uuid']);
        return $data;
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $this->productService->uploadThumbnail($request->file('thumbnail'));
            return $this->successResponse([
                'path' => $path,
                'url'  => asset('storage/' . $path),
            ], 'Thumbnail uploaded.');
        }

        if ($request->hasFile('image')) {
            $path = $this->productService->uploadImage($request->file('image'));
            return $this->successResponse([
                'path' => $path,
                'url'  => asset('storage/' . $path),
            ], 'Image uploaded.');
        }

        return response()->json(['message' => 'No file provided.'], 422);
    }
}
