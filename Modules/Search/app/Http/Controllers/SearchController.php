<?php

namespace Modules\Search\Http\Controllers;

use Modules\Search\Services\SearchService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController
{
    use ApiResponse;

    public function __construct(
        private SearchService $searchService
    ) {}

    /**
     * Full-text product search with faceted filtering.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'             => 'nullable|string|min:2|max:200',
            'category_uuid' => 'nullable|exists:categories,uuid',
            'brand_uuid'    => 'nullable|exists:brands,uuid',
            'min_price'     => 'nullable|numeric|min:0',
            'max_price'     => 'nullable|numeric|min:0',
            'in_stock'      => 'nullable|boolean',
            'rating'        => 'nullable|numeric|min:1|max:5',
            'sort'          => 'nullable|in:relevance,price_asc,price_desc,newest,popular,rating',
            'per_page'      => 'nullable|integer|min:1|max:100',
            'page'          => 'nullable|integer|min:1',
        ]);

        // Map public uuid filters to internal integer ids for the search service
        if (!empty($validated['category_uuid'])) {
            $validated['category_id'] = \Modules\Catalog\Models\Category::findByUuidOrFail($validated['category_uuid'])->id;
        }
        if (!empty($validated['brand_uuid'])) {
            $validated['brand_id'] = \Modules\Catalog\Models\Brand::findByUuidOrFail($validated['brand_uuid'])->id;
        }

        $results = $this->searchService->search(
            $validated['q'] ?? '',
            $validated,
            $validated['per_page'] ?? config('search.search.per_page', 20)
        );

        // When Elasticsearch returns raw data (not paginator), wrap it manually
        if (is_array($results)) {
            return response()->json([
                'success' => true,
                'message' => 'Search results',
                'data'    => $results['products'] ?? [],
                'pagination' => [
                    'total'        => $results['total'] ?? 0,
                    'per_page'     => $validated['per_page'] ?? 20,
                    'current_page' => $validated['page'] ?? 1,
                    'from'         => (($validated['page'] ?? 1) - 1) * ($validated['per_page'] ?? 20),
                ],
                'aggregations' => $results['aggregations'] ?? [],
                'took_ms'      => $results['took_ms'] ?? 0,
            ]);
        }

        return $this->paginatedResponse($results, 'Search results');
    }

    /**
     * Autocomplete suggestions for search input.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);

        $suggestions = $this->searchService->suggestions($request->q);

        return $this->successResponse($suggestions, 'Suggestions');
    }

    /**
     * Price range for matching products.
     */
    public function priceRange(Request $request): JsonResponse
    {
        $range = $this->searchService->priceRange(
            $request->filled('category_uuid')
                ? ['category_id' => \Modules\Catalog\Models\Category::findByUuid($request->category_uuid)?->id]
                : []
        );

        return $this->successResponse($range, 'Price range');
    }

    /**
     * Faceted data for search refinement.
     */
    public function facets(Request $request): JsonResponse
    {
        $facets = $this->searchService->getFacets($request->q ?? '');

        return $this->successResponse($facets, 'Facets');
    }

    /**
     * Popular search queries (from analytics).
     */
    public function popularSearches(Request $request): JsonResponse
    {
        $limit = min((int) ($request->limit ?? 10), 50);
        $searches = $this->searchService->popularSearches($limit);

        return $this->successResponse($searches, 'Popular searches');
    }
}
