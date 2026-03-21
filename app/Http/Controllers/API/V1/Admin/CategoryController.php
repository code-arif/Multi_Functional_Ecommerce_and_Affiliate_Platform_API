<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withTrashed()
                               ->with('parent')
                               ->withCount('products')
                               ->orderBy('sort_order')
                               ->get();
        return $this->successResponse(CategoryResource::collection($categories));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'parent_id'        => 'nullable|exists:categories,id',
            'description'      => 'nullable|string',
            'image'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'icon'             => 'nullable|string|max:50',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'sort_order'       => 'nullable|integer',
            'is_active'        => 'boolean',
        ]);

        $data = $request->except('image');
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($data);
        Cache::forget('category_tree');

        return $this->createdResponse(new CategoryResource($category), 'Category created.');
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->except('image');
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        $category->update($data);
        Cache::forget('category_tree');

        return $this->successResponse(new CategoryResource($category), 'Category updated.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        Cache::forget('category_tree');
        return $this->noContentResponse('Category deleted.');
    }
}
