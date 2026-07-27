<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Http\Resources\CategoryResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $categories = Category::with(['parent', 'children' => fn($q) => $q->orderBy('sort_order')])
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id'      => 'nullable|exists:categories,id',
            'name'           => 'required|string|max:100',
            'description'    => 'nullable|string|max:1000',
            'icon'           => 'nullable|string|max:255',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'meta_title'     => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'  => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer|min:0',
            'is_featured'    => 'boolean',
            'is_active'      => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }
        if ($request->hasFile('banner')) {
            $validated['banner'] = $request->file('banner')->store('categories/banners', 'public');
        }

        $category = Category::create($validated);

        return $this->createdResponse(new CategoryResource($category->load('parent')), 'Category created.');
    }

    public function update(Category $category, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id'      => 'nullable|exists:categories,id',
            'name'           => 'sometimes|string|max:100',
            'description'    => 'nullable|string|max:1000',
            'icon'           => 'nullable|string|max:255',
            'image'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'meta_title'     => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'  => 'nullable|string|max:255',
            'sort_order'     => 'nullable|integer|min:0',
            'is_featured'    => 'boolean',
            'is_active'      => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }
        if ($request->hasFile('banner')) {
            $validated['banner'] = $request->file('banner')->store('categories/banners', 'public');
        }

        $category->update($validated);

        return $this->successResponse(new CategoryResource($category->fresh()->load('parent')), 'Category updated.');
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return $this->errorResponse('Cannot delete category with associated products.', null, 400);
        }
        $category->delete();
        return $this->noContentResponse('Category deleted.');
    }
}
