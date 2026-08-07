<?php

namespace Modules\Catalog\Http\Controllers;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Transformers\CategoryResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CategoryController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->with(['children' => fn($q) => $q->active()->orderBy('sort_order')])
            ->root()
            ->orderBy('sort_order')
            ->get();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::active()
            ->with(['children' => fn($q) => $q->active()->orderBy('sort_order')])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->successResponse(new CategoryResource($category));
    }
}
