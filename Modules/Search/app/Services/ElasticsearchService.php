<?php

namespace Modules\Search\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use Modules\Product\Models\Product;;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
    private ?Client $client = null;
    private bool $available = false;
    private string $productsIndex;

    public function __construct()
    {
        $this->productsIndex = config('search.index.products', 'products');
        $this->connect();
    }

    /**
     * Attempt to connect to Elasticsearch.
     */
    private function connect(): void
    {
        try {
            $hosts = config('search.elasticsearch.hosts', ['localhost:9200']);
            $this->client = ClientBuilder::create()
                ->setHosts($hosts)
                ->setRetries(config('search.elasticsearch.retries', 2))
                ->build();

            // Ping to verify connection
            $this->client->ping();
            $this->available = true;
        } catch (\Throwable $e) {
            $this->available = false;
            $this->client = null;
            Log::warning('Elasticsearch connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if Elasticsearch is available.
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Get the raw Elasticsearch client.
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    // ─── Index Management ─────────────────────────────────────────

    /**
     * Create the products index with appropriate mappings.
     */
    public function createIndex(): bool
    {
        if (!$this->available) return false;

        try {
            $params = [
                'index' => $this->productsIndex,
                'body' => [
                    'settings' => config('search.index.settings', [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                    ]),
                    'mappings' => [
                        'properties' => [
                            'id'                => ['type' => 'integer'],
                            'name'              => ['type' => 'text', 'analyzer' => 'standard', 'fields' => ['keyword' => ['type' => 'keyword'], 'completion' => ['type' => 'completion']]],
                            'slug'              => ['type' => 'keyword'],
                            'sku'               => ['type' => 'keyword'],
                            'short_description' => ['type' => 'text', 'analyzer' => 'standard'],
                            'description'       => ['type' => 'text', 'analyzer' => 'standard'],
                            'tags'              => ['type' => 'keyword'],
                            'category_id'       => ['type' => 'integer'],
                            'category_name'     => ['type' => 'keyword'],
                            'category_slug'     => ['type' => 'keyword'],
                            'brand_id'          => ['type' => 'integer'],
                            'brand_name'        => ['type' => 'keyword'],
                            'brand_slug'        => ['type' => 'keyword'],
                            'price'             => ['type' => 'float'],
                            'sale_price'        => ['type' => 'float'],
                            'average_rating'    => ['type' => 'float'],
                            'total_sold'        => ['type' => 'integer'],
                            'stock_status'      => ['type' => 'keyword'],
                            'is_featured'       => ['type' => 'boolean'],
                            'status'            => ['type' => 'keyword'],
                            'thumbnail'         => ['type' => 'keyword', 'index' => false],
                            'created_at'        => ['type' => 'date'],
                        ],
                    ],
                ],
            ];

            $this->client->indices()->create($params);
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to create Elasticsearch index: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete the products index.
     */
    public function deleteIndex(): bool
    {
        if (!$this->available) return false;

        try {
            $this->client->indices()->delete(['index' => $this->productsIndex]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if the index exists.
     */
    public function indexExists(): bool
    {
        if (!$this->available) return false;

        try {
            return $this->client->indices()->exists(['index' => $this->productsIndex])->asBool();
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ─── Document Indexing ────────────────────────────────────────

    /**
     * Index a single product.
     */
    public function indexProduct(Product $product): bool
    {
        if (!$this->available) return false;

        try {
            $this->ensureIndexExists();

            $doc = $this->buildProductDocument($product);
            $this->client->index([
                'index' => $this->productsIndex,
                'id'    => (string) $product->id,
                'body'  => $doc,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error("Failed to index product {$product->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk index multiple products.
     */
    public function bulkIndexProducts(iterable $products): array
    {
        if (!$this->available) return ['success' => 0, 'failed' => 0];

        try {
            $this->ensureIndexExists();

            $params = ['body' => []];
            $count = 0;

            foreach ($products as $product) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $this->productsIndex,
                        '_id'    => (string) $product->id,
                    ],
                ];
                $params['body'][] = $this->buildProductDocument($product);
                $count++;

                // Bulk in batches of 100
                if ($count % 100 === 0) {
                    $this->client->bulk($params);
                    $params = ['body' => []];
                }
            }

            // Index remaining
            if (!empty($params['body'])) {
                $this->client->bulk($params);
            }

            return ['success' => $count, 'failed' => 0];
        } catch (\Throwable $e) {
            Log::error('Bulk indexing failed: ' . $e->getMessage());
            return ['success' => 0, 'failed' => $count ?? 0];
        }
    }

    /**
     * Remove a product from the index.
     */
    public function removeProduct(int $productId): bool
    {
        if (!$this->available) return false;

        try {
            $this->client->delete([
                'index' => $this->productsIndex,
                'id'    => (string) $productId,
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Reindex all active products.
     */
    public function reindexAll(): array
    {
        if (!$this->available) return ['success' => 0, 'failed' => 0, 'error' => 'Elasticsearch unavailable'];

        try {
            // Delete and recreate index
            $this->deleteIndex();
            $this->createIndex();

            // Bulk index all active products
            $products = Product::active()->with(['category', 'brand'])->cursor();
            return $this->bulkIndexProducts($products);
        } catch (\Throwable $e) {
            Log::error('Reindex failed: ' . $e->getMessage());
            return ['success' => 0, 'failed' => 0, 'error' => $e->getMessage()];
        }
    }

    // ─── Searching ────────────────────────────────────────────────

    /**
     * Perform a full-text search with faceted filtering.
     */
    public function search(string $query, array $filters = [], int $from = 0, int $size = 20): array
    {
        if (!$this->available) {
            return ['error' => 'Elasticsearch unavailable'];
        }

        try {
            $this->ensureIndexExists();

            $mustQueries = [
                'must' => [
                    'multi_match' => [
                        'query'  => $query,
                        'fields' => [
                            'name^5',
                            'sku^4',
                            'short_description^2',
                            'description',
                            'tags^3',
                            'category_name^2',
                            'brand_name^2',
                        ],
                        'type'       => 'best_fields',
                        'fuzziness'  => 'AUTO',
                        'minimum_should_match' => '70%',
                    ],
                ],
            ];

            // Always filter by active status
            $filterClauses = [
                ['term' => ['status' => 'active']],
            ];

            if (!empty($filters['category_id'])) {
                $filterClauses[] = ['term' => ['category_id' => (int) $filters['category_id']]];
            }
            if (!empty($filters['brand_id'])) {
                $filterClauses[] = ['term' => ['brand_id' => (int) $filters['brand_id']]];
            }
            if (isset($filters['min_price'])) {
                $filterClauses[] = ['range' => ['sale_price' => ['gte' => (float) $filters['min_price']]]];
            }
            if (isset($filters['max_price'])) {
                $filterClauses[] = ['range' => ['price' => ['lte' => (float) $filters['max_price']]]];
            }
            if (!empty($filters['in_stock'])) {
                $filterClauses[] = ['term' => ['stock_status' => 'in_stock']];
            }
            if (!empty($filters['rating'])) {
                $filterClauses[] = ['range' => ['average_rating' => ['gte' => (float) $filters['rating']]]];
            }

            // Sort
            $sort = match ($filters['sort'] ?? 'relevance') {
                'price_asc'  => [['price' => ['order' => 'asc']]],
                'price_desc' => [['price' => ['order' => 'desc']]],
                'newest'     => [['created_at' => ['order' => 'desc']]],
                'popular'    => [['total_sold' => ['order' => 'desc']]],
                'rating'     => [['average_rating' => ['order' => 'desc']]],
                default      => [['_score' => ['order' => 'desc']]],
            };

            $params = [
                'index' => $this->productsIndex,
                'body'  => [
                    'query' => [
                        'bool' => [
                            'must'   => [$mustQueries],
                            'filter' => $filterClauses,
                        ],
                    ],
                    'sort'  => $sort,
                    'from'  => $from,
                    'size'  => $size,
                    'aggs'  => [
                        'category_ids' => [
                            'terms' => ['field' => 'category_id', 'size' => 50],
                        ],
                        'brand_ids' => [
                            'terms' => ['field' => 'brand_id', 'size' => 50],
                        ],
                        'price_min' => [
                            'min' => ['field' => 'price'],
                        ],
                        'price_max' => [
                            'max' => ['field' => 'price'],
                        ],
                        'ratings' => [
                            'range' => [
                                'field' => 'average_rating',
                                'ranges' => [
                                    ['from' => 4],
                                    ['from' => 3, 'to' => 4],
                                    ['from' => 2, 'to' => 3],
                                    ['from' => 1, 'to' => 2],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $response = $this->client->search($params);
            return $this->parseSearchResponse($response);
        } catch (\Throwable $e) {
            Log::error('Elasticsearch search failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get autocomplete suggestions using completion suggester on name.completion.
     */
    public function suggest(string $query, int $size = 10): array
    {
        if (!$this->available) return [];

        try {
            $this->ensureIndexExists();

            $params = [
                'index' => $this->productsIndex,
                'body'  => [
                    'suggest' => [
                        'name-suggest' => [
                            'prefix' => $query,
                            'completion' => [
                                'field'   => 'name.completion',
                                'size'    => $size,
                                'fuzzy'   => ['fuzziness' => 1],
                            ],
                        ],
                    ],
                    'size' => 0, // Only need suggestions, not hits
                ],
            ];

            $response = $this->client->search($params);
            $suggestions = [];

            if (isset($response['suggest']['name-suggest'])) {
                foreach ($response['suggest']['name-suggest'] as $entry) {
                    foreach ($entry['options'] as $option) {
                        $suggestions[] = $option['text'];
                    }
                }
            }

            // Deduplicate and limit
            $suggestions = array_values(array_unique($suggestions));
            return array_slice($suggestions, 0, $size);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Build the Elasticsearch document body for a product.
     */
    private function buildProductDocument(Product $product): array
    {
        return [
            'id'                => $product->id,
            'name'              => $product->name,
            'slug'              => $product->slug,
            'sku'               => $product->sku,
            'short_description' => $product->short_description,
            'description'       => strip_tags($product->description ?? ''),
            'tags'              => is_array($product->tags) ? $product->tags : [],
            'category_id'       => $product->category_id,
            'category_name'     => $product->category?->name ?? '',
            'category_slug'     => $product->category?->slug ?? '',
            'brand_id'          => $product->brand_id,
            'brand_name'        => $product->brand?->name ?? '',
            'brand_slug'        => $product->brand?->slug ?? '',
            'price'             => (float) ($product->sale_price ?? $product->price),
            'sale_price'        => $product->sale_price ? (float) $product->sale_price : null,
            'average_rating'    => (float) $product->average_rating,
            'total_sold'        => (int) $product->total_sold,
            'stock_status'      => $product->stock_status,
            'is_featured'       => (bool) $product->is_featured,
            'status'            => $product->status,
            'thumbnail'         => $product->thumbnail,
            'created_at'        => $product->created_at?->toIso8601String(),
        ];
    }

    /**
     * Parse the Elasticsearch search response into a structured array.
     */
    private function parseSearchResponse(array $response): array
    {
        $hits = $response['hits']['hits'] ?? [];
        $total = $response['hits']['total']['value'] ?? 0;
        $aggregations = $response['aggregations'] ?? [];

        $products = [];
        foreach ($hits as $hit) {
            $source = $hit['_source'];
            $source['_score'] = $hit['_score'] ?? null;
            $products[] = $source;
        }

        // Extract facet data from aggregations
        $categoryIds = [];
        $brandIds = [];
        if (isset($aggregations['category_ids']['buckets'])) {
            foreach ($aggregations['category_ids']['buckets'] as $bucket) {
                $categoryIds[] = ['id' => (int) $bucket['key'], 'count' => $bucket['doc_count']];
            }
        }
        if (isset($aggregations['brand_ids']['buckets'])) {
            foreach ($aggregations['brand_ids']['buckets'] as $bucket) {
                $brandIds[] = ['id' => (int) $bucket['key'], 'count' => $bucket['doc_count']];
            }
        }

        return [
            'products'    => $products,
            'total'       => $total,
            'from'        => $response['hits']['from'] ?? 0,
            'size'        => $response['hits']['size'] ?? 20,
            'max_score'   => $response['hits']['max_score'] ?? null,
            'aggregations' => [
                'category_ids' => $categoryIds,
                'brand_ids'    => $brandIds,
                'price_min'    => $aggregations['price_min']['value'] ?? 0,
                'price_max'    => $aggregations['price_max']['value'] ?? 0,
            ],
            'took_ms'     => $response['took'] ?? 0,
        ];
    }

    /**
     * Ensure the index exists before performing operations.
     * Logs a warning if index is missing rather than silently creating it.
     */
    private function ensureIndexExists(): void
    {
        if (!$this->indexExists()) {
            Log::warning('Elasticsearch products index not found, attemping auto-creation.');
            $this->createIndex();
        }
    }
}
