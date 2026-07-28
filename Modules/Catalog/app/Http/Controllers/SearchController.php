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

    /**
     * Full-text product search with faceted filtering.
     * Supports Elasticsearch (returns array with aggregations)
     * and database fallback (returns paginator).
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'nullable|string|min:2|max:200']);

        $results = $this->searchService->search(
            $request->q ?? '',
            $request->only(['category_id', 'brand_id', 'min_price', 'max_price', 'sort', 'in_stock', 'rating', 'page']),
            $request->per_page ?? 20
        );

        // Elasticsearch response (array with aggregations)
        if (is_array($results)) {
            return response()->json([
                'success' => true,
                'message' => 'Search results',
                'data'    => $results['products'] ?? [],
                'pagination' => [
                    'total'        => $results['total'] ?? 0,
                    'per_page'     => (int) ($results['size'] ?? $request->per_page ?? 20),
                    'current_page' => (int) ($request->page ?? 1),
                    'last_page'    => (int) ceil(($results['total'] ?? 0) / max(($results['size'] ?? 20), 1)),
                ],
                'aggregations' => $results['aggregations'] ?? [],
                'took_ms'      => $results['took_ms'] ?? 0,
            ]);
        }

        // Database paginator response
        return $this->paginatedResponse($results, 'Search results');
    }

    public function suggestions(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);

        $suggestions = $this->searchService->suggestions($request->q);

        return $this->successResponse($suggestions, 'Suggestions');
    }

    public function priceRange(Request $request): JsonResponse
    {
        $range = $this->searchService->priceRange(
            $request->only(['category_id'])
        );

        return $this->successResponse($range, 'Price range');
    }

    public function facets(Request $request): JsonResponse
    {
        $facets = $this->searchService->getFacets($request->q ?? '');

        return $this->successResponse($facets, 'Facets');
    }
}
