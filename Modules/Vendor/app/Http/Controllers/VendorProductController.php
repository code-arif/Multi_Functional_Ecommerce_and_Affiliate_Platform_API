<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\VendorProductPrice;
use Modules\Catalog\Services\ProductService;
use Modules\Catalog\Http\Resources\ProductResource;
use Modules\Catalog\Http\Resources\ProductListResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorProductController
{
    use ApiResponse;

    public function __construct(private ProductService $productService) {}

    /**
     * GET /api/v1/vendor/products
     * List products this vendor sells (via vendor_product_prices).
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }
        if ($vendor->status !== 'active') {
            return $this->errorResponse('Your vendor account is not active.', null, 403);
        }

        $productIds = VendorProductPrice::where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->pluck('product_id');

        $products = Product::with(['category', 'brand', 'primaryImage'])
            ->whereIn('id', $productIds)
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            }))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        // Attach vendor-specific pricing to each product
        $products->load(['vendorProductPrices' => fn($q) => $q->where('vendor_id', $vendor->id)]);

        return $this->paginatedResponse(
            $products->through(fn($product) => array_merge(
                (new ProductListResource($product))->toArray($request),
                [
                    'vendor_price' => $product->vendorProductPrices->first()?->only([
                        'price', 'sale_price', 'stock_quantity', 'is_active',
                    ]),
                ]
            ))
        );
    }

    /**
     * POST /api/v1/vendor/products
     * Link a product to vendor with custom pricing, or create a new product.
     */
    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $validated = $request->validate([
            'product_id'  => 'nullable|exists:products,id',
            'name'        => 'required_without:product_id|string|max:255',
            'sku'         => 'nullable|string|max:100|unique:products,sku',
            'price'       => 'nullable|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0|lte:price',
            'stock_quantity' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'thumbnail'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status'      => 'nullable|in:active,inactive,draft',
        ]);

        // If linking to an existing product
        if (!empty($validated['product_id'])) {
            $product = Product::findOrFail($validated['product_id']);

            VendorProductPrice::updateOrCreate(
                ['vendor_id' => $vendor->id, 'product_id' => $product->id],
                [
                    'price'          => $validated['price'] ?? $product->price,
                    'sale_price'     => $validated['sale_price'] ?? null,
                    'stock_quantity' => $validated['stock_quantity'] ?? $product->stock_quantity,
                    'is_active'      => true,
                ]
            );

            return $this->successResponse(
                new ProductResource($product->load(['vendorProductPrices' => fn($q) => $q->where('vendor_id', $vendor->id)])),
                'Product linked to your shop.'
            );
        }

        // Create a new product as a vendor
        $data = $validated;
        $data['status'] = $data['status'] ?? 'draft';

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->productService->uploadThumbnail($request->file('thumbnail'));
        }

        $product = $this->productService->createProduct($data);

        // Link to vendor
        VendorProductPrice::create([
            'vendor_id'      => $vendor->id,
            'product_id'     => $product->id,
            'price'          => $data['price'] ?? $product->price,
            'sale_price'     => $data['sale_price'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'is_active'      => true,
        ]);

        return $this->createdResponse(
            new ProductResource($product->load('vendorProductPrices')),
            'Product created successfully.'
        );
    }

    /**
     * GET /api/v1/vendor/products/{product}
     * Show product details with vendor pricing.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $vendor = $request->user()->vendor;

        // Verify vendor sells this product
        $vendorPrice = VendorProductPrice::where('vendor_id', $vendor->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $product->load(['images', 'variants', 'attributes.values', 'category', 'brand']);

        $data = (new ProductResource($product))->toArray($request);
        $data['vendor_pricing'] = $vendorPrice->only([
            'price', 'sale_price', 'stock_quantity', 'low_stock_threshold',
            'manage_stock', 'stock_status', 'is_active',
        ]);

        return $this->successResponse($data, 'Product details retrieved.');
    }

    /**
     * POST /api/v1/vendor/products/{product}?_method=PUT
     * Update vendor's product pricing/stock, or update product details.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $vendorPrice = VendorProductPrice::where('vendor_id', $vendor->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $validated = $request->validate([
            'price'          => 'nullable|numeric|min:0',
            'sale_price'     => 'nullable|numeric|min:0|lte:price',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'manage_stock'   => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
        ]);

        $vendorPrice->update($validated);

        return $this->successResponse([
            'vendor_pricing' => $vendorPrice->fresh()->only([
                'price', 'sale_price', 'stock_quantity', 'low_stock_threshold',
                'manage_stock', 'stock_status', 'is_active',
            ]),
        ], 'Product pricing updated.');
    }

    /**
     * DELETE /api/v1/vendor/products/{product}
     * Remove product from vendor's shop (soft-delete the pivot).
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $vendorPrice = VendorProductPrice::where('vendor_id', $vendor->id)
            ->where('product_id', $product->id)
            ->firstOrFail();

        $vendorPrice->update(['is_active' => false]);

        return $this->successResponse(null, 'Product removed from your shop.');
    }
}
