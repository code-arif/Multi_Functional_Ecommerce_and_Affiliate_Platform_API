<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AffiliateProductResource;
use App\Models\AffiliateProduct;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateProductController extends Controller
{
    use ApiResponse;

    /**
     * Affiliate product list
     */
    public function index(Request $request): JsonResponse
    {
        $products = AffiliateProduct::with('category')
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return $this->paginatedResponse(AffiliateProductResource::collection($products));
    }

    /**
     * Affiliate product store
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'category_id'     => 'nullable|exists:categories,id',
            'description'     => 'nullable|string',
            'thumbnail'       => 'nullable|image|max:2048',
            'affiliate_link'  => 'required|url',
            'source_platform' => 'required|string|max:100',
            'display_price'   => 'nullable|numeric|min:0',
            'is_active'       => 'boolean',
        ]);
        $data = $request->except('thumbnail');
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('affiliates', 'public');
        }
        $product = AffiliateProduct::create($data);
        return $this->createdResponse(new AffiliateProductResource($product), 'Affiliate product created.');
    }

    /**
     * Affiliate product update
     */
    public function update(Request $request, AffiliateProduct $affiliateProduct): JsonResponse
    {
        $data = $request->except('thumbnail');
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('affiliates', 'public');
        }
        $affiliateProduct->update($data);
        return $this->successResponse(new AffiliateProductResource($affiliateProduct), 'Updated.');
    }

    /**
     * Affiliate product delete
     */
    public function destroy(AffiliateProduct $affiliateProduct): JsonResponse
    {
        $affiliateProduct->delete();
        return $this->noContentResponse('Deleted.');
    }
}
