<?php

return [
    'currency'          => env('STORE_CURRENCY', 'BDT'),
    'currency_symbol'   => env('STORE_CURRENCY_SYMBOL', '৳'),
    'store_name'        => env('STORE_NAME', 'EcoShop'),
    'store_email'       => env('STORE_EMAIL', 'info@ecoshop.com'),
    'pagination'        => [
        'products_per_page'  => 20,
        'orders_per_page'    => 15,
        'reviews_per_page'   => 10,
    ],
    'cart' => [
        'guest_expiry_days'  => 7,
        'max_quantity'       => 100,
    ],
    'affiliate' => [
        'track_clicks'       => true,
    ],
    'image' => [
        'product_width'      => 800,
        'product_height'     => 800,
        'thumbnail_width'    => 300,
        'thumbnail_height'   => 300,
    ],
];
