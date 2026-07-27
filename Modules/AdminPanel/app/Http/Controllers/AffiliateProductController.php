<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Affiliate\Models\AffiliateProduct;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateProductController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $products = AffiliateProduct::withCount('clicks')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($products);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:200',
            'description'      => 'nullable|string',
            'price'            => 'required|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'affiliate_link'   => 'required|url|max:1000',
            'commission_type'  => 'required|string|in:fixed,percentage',
            'commission_value' => 'required|numeric|min:0',
            'is_featured'      => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('affiliate-products', 'public');
        }

        $product = AffiliateProduct::create($validated);

        return $this->createdResponse($product, 'Affiliate product created.');
    }

    public function update(AffiliateProduct $affiliateProduct, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'sometimes|string|max:200',
            'description'      => 'nullable|string',
            'price'            => 'sometimes|numeric|min:0',
            'sale_price'       => 'nullable|numeric|min:0',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'affiliate_link'   => 'sometimes|url|max:1000',
            'commission_type'  => 'sometimes|string|in:fixed,percentage',
            'commission_value' => 'sometimes|numeric|min:0',
            'is_featured'      => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('affiliate-products', 'public');
        }

        $affiliateProduct->update($validated);

        return $this->successResponse($affiliateProduct->fresh(), 'Affiliate product updated.');
    }

    public function destroy(AffiliateProduct $affiliateProduct): JsonResponse
    {
        $affiliateProduct->delete();
        return $this->noContentResponse('Affiliate product deleted.');
    }
}
