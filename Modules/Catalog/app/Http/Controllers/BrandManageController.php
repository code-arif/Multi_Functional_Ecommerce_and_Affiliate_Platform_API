<?php

namespace Modules\Catalog\Http\Controllers;

use Modules\Catalog\Http\Resources\BrandResource;
use Modules\Catalog\Http\Requests\StoreBrandRequest;
use Modules\Catalog\Http\Requests\UpdateBrandRequest;
use Modules\Catalog\Models\Brand;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandManageController
{
    use ApiResponse;

    // List all brands with optional search and pagination
    public function index(Request $request): JsonResponse
    {
        $brands = Brand::withCount('products')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse(BrandResource::collection($brands));
    }

    // Store a new brand
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create($data);

        return $this->createdResponse(new BrandResource($brand), 'Brand created.');
    }

    // Update an existing brand
    public function update(Brand $brand, UpdateBrandRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($data);

        return $this->successResponse(new BrandResource($brand->fresh()), 'Brand updated.');
    }

    // Delete a brand, ensuring it has no associated products
    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->exists()) {
            return $this->errorResponse('Cannot delete brand with associated products.', null, 400);
        }
        $brand->delete();
        return $this->noContentResponse('Brand deleted.');
    }
}
