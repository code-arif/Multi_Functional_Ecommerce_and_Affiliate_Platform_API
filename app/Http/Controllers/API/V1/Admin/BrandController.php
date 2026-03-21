<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponse;
    
    public function index(): JsonResponse
    {
        $brands = Brand::withCount('products')->orderBy('name')->get();
        return $this->successResponse($brands);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:brands,name',
            'description' => 'nullable|string',
            'logo'        => 'nullable|image|max:2048',
            'website'     => 'nullable|url',
            'is_active'   => 'boolean',
        ]);
        $data = $request->except('logo');
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }
        $brand = Brand::create($data);
        return $this->createdResponse($brand, 'Brand created.');
    }

    public function update(Request $request, Brand $brand): JsonResponse
    {
        $data = $request->except('logo');
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }
        $brand->update($data);
        return $this->successResponse($brand, 'Brand updated.');
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $brand->delete();
        return $this->noContentResponse('Brand deleted.');
    }
}

