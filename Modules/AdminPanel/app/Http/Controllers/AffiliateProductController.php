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
        $data = $this->mapCategoryUuid($request->validated());

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('affiliate-products', 'public');
        }

        $product = AffiliateProduct::create($data);

        return $this->createdResponse(new AffiliateProductResource($product), 'Affiliate product created.');
    }

    public function update(AffiliateProduct $affiliateProduct, UpdateAffiliateProductRequest $request): JsonResponse
    {
        $data = $this->mapCategoryUuid($request->validated());

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('affiliate-products', 'public');
        }

        $affiliateProduct->update($data);

        return $this->successResponse(new AffiliateProductResource($affiliateProduct->fresh()), 'Affiliate product updated.');
    }

    /**
     * Map public category_uuid reference to internal category_id.
     */
    private function mapCategoryUuid(array $data): array
    {
        if (array_key_exists('category_uuid', $data)) {
            $data['category_id'] = !empty($data['category_uuid'])
                ? \Modules\Catalog\Models\Category::findByUuidOrFail($data['category_uuid'])->id
                : null;
        }
        unset($data['category_uuid']);
        return $data;
    }

    public function destroy(AffiliateProduct $affiliateProduct): JsonResponse
    {
        $affiliateProduct->delete();
        return $this->noContentResponse('Affiliate product deleted.');
    }
}
