<?php

namespace Modules\Catalog\Http\Controllers;

use Modules\Search\Services\SearchService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController
{
    use ApiResponse;

    public function __construct(private SearchService $searchService) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:200']);

        $products = $this->searchService->search(
            $request->q,
            $request->only(['category_id', 'brand_id', 'min_price', 'max_price', 'sort']),
            $request->per_page ?? 20
        );

        return $this->paginatedResponse($products);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);

        $suggestions = $this->searchService->suggestions($request->q);

        return $this->successResponse($suggestions);
    }

    public function priceRange(Request $request): JsonResponse
    {
        $range = $this->searchService->priceRange(
            $request->only(['category_id'])
        );

        return $this->successResponse($range);
    }

    public function facets(Request $request): JsonResponse
    {
        $facets = $this->searchService->getFacets($request->q ?? '');

        return $this->successResponse($facets);
    }
}
