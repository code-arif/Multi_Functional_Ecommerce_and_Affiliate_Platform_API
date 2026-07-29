<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\AdminPanel\Http\Resources\AffiliateProductResource;
use Modules\AdminPanel\Http\Requests\StoreAffiliateProductRequest;
use Modules\AdminPanel\Http\Requests\UpdateAffiliateProductRequest;
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

        return $this->paginatedResponse(AffiliateProductResource::collection($products));
    }

    public function store(StoreAffiliateProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('affiliate-products', 'public');
        }

        $product = AffiliateProduct::create($data);

        return $this->createdResponse(new AffiliateProductResource($product), 'Affiliate product created.');
    }

    public function update(AffiliateProduct $affiliateProduct, UpdateAffiliateProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('affiliate-products', 'public');
        }

        $affiliateProduct->update($data);

        return $this->successResponse(new AffiliateProductResource($affiliateProduct->fresh()), 'Affiliate product updated.');
    }

    public function destroy(AffiliateProduct $affiliateProduct): JsonResponse
    {
        $affiliateProduct->delete();
        return $this->noContentResponse('Affiliate product deleted.');
    }
}
