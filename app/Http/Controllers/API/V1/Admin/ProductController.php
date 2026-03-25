<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(private ProductService $productService) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['category', 'brand'])
            ->when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->withCount('reviews')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(
            ProductListResource::collection($products)
        );
    }

    /**
     * Store product form admin dashboard
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();

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
        $data = $request->validated();

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
     * POST /api/v1/admin/products/upload-image
     * Handles both gallery images AND thumbnail uploads
     */
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
