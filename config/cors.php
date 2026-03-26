<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    | Only API routes are CORS-enabled. Web routes are not exposed.
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    | In production these are loaded from env. Never use ['*'] in production.
    */
    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:4173/'),
        env('ADMIN_URL',    'http://localhost:3004/'),
    ]),

    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    */
    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Requested-With',
        'X-Session-ID',     // Guest cart identifier
        'X-CSRF-TOKEN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    */
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    /*
    |--------------------------------------------------------------------------
    | Max Age (preflight cache)
    |--------------------------------------------------------------------------
    | 2 hours = 7200 seconds
    */
    'max_age' => 7200,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    | Required for Sanctum cookie-based SPA authentication.
    */
    'supports_credentials' => true,
];
