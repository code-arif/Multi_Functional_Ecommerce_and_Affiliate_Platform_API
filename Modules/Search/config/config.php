<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Configuration
    |--------------------------------------------------------------------------
    */
    'elasticsearch' => [
        'hosts' => explode(',', env('ELASTICSEARCH_HOSTS', 'localhost:9200')),
        'retries' => env('ELASTICSEARCH_RETRIES', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Settings
    |--------------------------------------------------------------------------
    */
    'index' => [
        'products' => env('ELASTICSEARCH_PRODUCTS_INDEX', 'products'),
        'settings' => [
            'number_of_shards' => env('ELASTICSEARCH_SHARDS', 1),
            'number_of_replicas' => env('ELASTICSEARCH_REPLICAS', 0),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    */
    'search' => [
        'per_page' => 20,
        'min_query_length' => 2,
        'max_query_length' => 200,
        'suggestions_count' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    | When true, falls back to database LIKE search when Elasticsearch
    | is unavailable. When false, throws an exception.
    */
    'fallback_to_database' => env('ELASTICSEARCH_FALLBACK', true),
];
