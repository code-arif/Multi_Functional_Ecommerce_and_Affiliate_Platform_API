<?php

namespace Modules\Search\Http\Controllers;

use Modules\Search\Services\SearchService;
use Modules\Search\Services\ElasticsearchService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController
{
    use ApiResponse;

    public function __construct(
        private SearchService $searchService,
        private ElasticsearchService $elasticsearch,
    ) {}

    /**
     * Search analytics overview.
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->period ?? '7_days';

        if (!in_array($period, ['24h', '7_days', '30_days'])) {
            return $this->errorResponse('Invalid period. Use: 24h, 7_days, 30_days.', null, 422);
        }

        $data = $this->searchService->getAnalytics($period);

        return $this->successResponse($data, 'Search analytics');
    }

    /**
     * Popular search queries.
     */
    public function popularSearches(Request $request): JsonResponse
    {
        $limit = min((int) ($request->limit ?? 20), 100);
        $searches = $this->searchService->popularSearches($limit);

        return $this->successResponse($searches, 'Popular searches');
    }

    /**
     * Searches that returned zero results (for optimization).
     */
    public function zeroResultSearches(Request $request): JsonResponse
    {
        $limit = min((int) ($request->limit ?? 20), 100);
        $searches = $this->searchService->zeroResultSearches($limit);

        return $this->successResponse($searches, 'Zero-result searches');
    }

    /**
     * Reindex all products into Elasticsearch.
     */
    public function reindex(): JsonResponse
    {
        if (!$this->elasticsearch->isAvailable()) {
            return $this->errorResponse('Elasticsearch is not available. Check your configuration.', null, 503);
        }

        $result = $this->elasticsearch->reindexAll();

        $message = $result['error'] ?? false
            ? 'Reindex completed with errors. Check the logs.'
            : "Reindex completed. {$result['success']} products indexed.";

        return $this->successResponse($result, $message);
    }

    /**
     * Get Elasticsearch connection status.
     */
    public function status(): JsonResponse
    {
        $available = $this->elasticsearch->isAvailable();
        $indexExists = $available ? $this->elasticsearch->indexExists() : false;

        return $this->successResponse([
            'available'       => $available,
            'index_exists'    => $indexExists,
            'index_name'      => config('search.index.products', 'products'),
            'hosts'           => config('search.elasticsearch.hosts', ['localhost:9200']),
            'fallback_active' => config('search.fallback_to_database', true),
        ], 'Elasticsearch status');
    }
}
