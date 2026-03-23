<?php

// config/ecommerce.php

return [

    /*
    |--------------------------------------------------------------------------
    | Store Identity
    |--------------------------------------------------------------------------
    */
    'store_name'     => env('STORE_NAME', 'EcoShop'),
    'store_email'    => env('STORE_EMAIL', 'info@ecoshop.com'),
    'store_phone'    => env('STORE_PHONE', '+8801700000000'),
    'frontend_url'   => env('FRONTEND_URL', 'http://localhost:3000'),
    'admin_url'      => env('ADMIN_URL', 'http://localhost:3001'),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */
    'currency'        => env('STORE_CURRENCY', 'BDT'),
    'currency_symbol' => env('STORE_CURRENCY_SYMBOL', '৳'),

    /*
    |--------------------------------------------------------------------------
    | Pagination Defaults
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'products_per_page' => env('PRODUCTS_PER_PAGE', 20),
        'orders_per_page'   => 15,
        'reviews_per_page'  => 10,
        'users_per_page'    => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    */
    'cart' => [
        'guest_expiry_days' => 7,
        'user_expiry_days'  => 30,
        'max_quantity'      => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    */
    'shipping' => [
        'default_charge'   => env('SHIPPING_CHARGE', 60),
        'free_over'        => env('FREE_SHIPPING_OVER', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    */
    'image' => [
        'product_disk'     => 'public',
        'max_size_kb'      => 5120,
        'thumbnail_width'  => 300,
        'thumbnail_height' => 300,
        'gallery_width'    => 800,
        'gallery_height'   => 800,
    ],

    /*
    |--------------------------------------------------------------------------
    | Affiliate
    |--------------------------------------------------------------------------
    */
    'affiliate' => [
        'track_clicks'  => env('AFFILIATE_TRACK_CLICKS', true),
        'redirect_delay'=> 0, // seconds before redirect
    ],

    /*
    |--------------------------------------------------------------------------
    | Features Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'reviews_require_purchase' => false,
        'review_auto_approve'      => false,
        'guest_checkout'           => true,
        'wishlist_for_guests'      => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Throttle
    |--------------------------------------------------------------------------
    */
    'throttle' => [
        'api'      => env('THROTTLE_API', '60,1'),       // 60 per minute
        'auth'     => env('THROTTLE_AUTH', '10,1'),      // 10 per minute
        'checkout' => env('THROTTLE_CHECKOUT', '5,1'),   // 5 per minute
        'search'   => env('THROTTLE_SEARCH', '30,1'),    // 30 per minute
    ],

];
