<?php

namespace App\Http\Controllers\API\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use App\Services\SearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;
    
    public function __construct(private SearchService $searchService) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q'          => 'nullable|string|max:200',
            'category'   => 'nullable|string',
            'brand'      => 'nullable|string',
            'min_price'  => 'nullable|numeric|min:0',
            'max_price'  => 'nullable|numeric|min:0',
            'rating'     => 'nullable|numeric|min:1|max:5',
            'in_stock'   => 'nullable|boolean',
            'sort'       => 'nullable|in:price_asc,price_desc,newest,popularity,rating',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        $results = $this->searchService->search($request->all());

        return $this->paginatedResponse(
            ProductListResource::collection($results),
            'Search results.'
        );
    }

    public function suggestions(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);
        $suggestions = $this->searchService->getSuggestions($request->q);
        return $this->successResponse($suggestions);
    }

    public function priceRange(): JsonResponse
    {
        return $this->successResponse($this->searchService->getPriceRange());
    }
}
