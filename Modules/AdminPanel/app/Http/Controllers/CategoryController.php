<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Catalog\Http\Requests\StoreCategoryRequest;
use Modules\Catalog\Http\Requests\UpdateCategoryRequest;
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

        return $this->paginatedResponse(CategoryResource::collection($categories));
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $this->mapParentUuid($request->validated());

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('categories/banners', 'public');
        }

        $category = Category::create($data);

        return $this->createdResponse(new CategoryResource($category->load('parent')), 'Category created.');
    }

    public function update(Category $category, UpdateCategoryRequest $request): JsonResponse
    {
        $data = $this->mapParentUuid($request->validated());

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('categories/banners', 'public');
        }

        $category->update($data);

        return $this->successResponse(new CategoryResource($category->fresh()->load('parent')), 'Category updated.');
    }

    /**
     * Map public parent_uuid reference to internal parent_id.
     */
    private function mapParentUuid(array $data): array
    {
        if (array_key_exists('parent_uuid', $data)) {
            $data['parent_id'] = !empty($data['parent_uuid'])
                ? Category::findByUuidOrFail($data['parent_uuid'])->id
                : null;
        }
        unset($data['parent_uuid']);
        return $data;
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
