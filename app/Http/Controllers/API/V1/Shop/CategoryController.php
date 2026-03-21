<?php

namespace App\Http\Controllers\API\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    use ApiResponse;
    
    public function index(): JsonResponse
    {
        $categories = Cache::remember('category_tree', now()->addHour(), function () {
            return Category::active()
                           ->roots()
                           ->with('allChildren')
                           ->withCount('products')
                           ->orderBy('sort_order')
                           ->get();
        });

        return $this->successResponse(
            CategoryResource::collection($categories),
            'Categories fetched.'
        );
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::active()
                            ->where('slug', $slug)
                            ->with(['parent', 'children'])
                            ->withCount('products')
                            ->firstOrFail();

        return $this->successResponse(new CategoryResource($category));
    }
}
