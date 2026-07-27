<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Catalog\Models\Brand;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $brands = Brand::withCount('products')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse($brands);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:1000',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'website'        => 'nullable|url|max:255',
            'meta_title'     => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer|min:0',
            'is_active'      => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create($validated);

        return $this->createdResponse($brand, 'Brand created.');
    }

    public function update(Brand $brand, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'sometimes|string|max:100',
            'description'    => 'nullable|string|max:1000',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'website'        => 'nullable|url|max:255',
            'meta_title'     => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer|min:0',
            'is_active'      => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($validated);

        return $this->successResponse($brand->fresh(), 'Brand updated.');
    }

    public function destroy(Brand $brand): JsonResponse
    {
        if ($brand->products()->exists()) {
            return $this->errorResponse('Cannot delete brand with associated products.', null, 400);
        }
        $brand->delete();
        return $this->noContentResponse('Brand deleted.');
    }
}
