<?php

namespace App\Http\Controllers\API\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductListResource;
use Modules\Search\Services\SearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use ApiResponse;

    public function __construct(private SearchService $searchService) {}

    /**
     * Search products using Elasticsearch (or database fallback).
     */
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

        $results = $this->searchService->search(
            $request->q ?? '',
            $request->all(),
            $request->per_page ?? 20
        );

        // Elasticsearch response (array)
        if (is_array($results)) {
            return response()->json([
                'success' => true,
                'message' => 'Search results.',
                'data'    => $results['products'] ?? [],
                'pagination' => [
                    'total'        => $results['total'] ?? 0,
                    'per_page'     => (int) ($request->per_page ?? 20),
                    'current_page' => (int) ($request->page ?? 1),
                    'last_page'    => (int) ceil(($results['total'] ?? 0) / max((int) ($request->per_page ?? 20), 1)),
                ],
                'aggregations' => $results['aggregations'] ?? [],
            ]);
        }

        // Database paginator response
        return $this->paginatedResponse(
            ProductListResource::collection($results),
            'Search results.'
        );
    }

    /**
     * Autocomplete suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);
        $suggestions = $this->searchService->suggestions($request->q);
        return $this->successResponse($suggestions);
    }

    /**
     * Get price range for all active products.
     */
    public function priceRange(): JsonResponse
    {
        $range = $this->searchService->priceRange();
        return $this->successResponse($range);
    }
}
