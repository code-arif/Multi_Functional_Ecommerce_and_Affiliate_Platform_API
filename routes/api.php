<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module API Routes (loaded in order)
| Each module owns its own routes, following the modular architecture.
|--------------------------------------------------------------------------
*/

// Auth module routes (auth, profile, addresses, devices, OTP, passwordless login)
Route::prefix('v1')->group(base_path('Modules/Auth/routes/api.php'));

// Catalog module routes (products, categories)
Route::prefix('v1')->group(base_path('Modules/Catalog/routes/api.php'));

// Cart module routes
Route::prefix('v1')->group(base_path('Modules/Cart/routes/api.php'));

// Checkout module routes
Route::prefix('v1')->group(base_path('Modules/Checkout/routes/api.php'));

// Orders module routes
Route::prefix('v1')->group(base_path('Modules/Orders/routes/api.php'));

// Reviews module routes
Route::prefix('v1')->group(base_path('Modules/Reviews/routes/api.php'));

// CMS module routes (public CMS, SEO)
Route::prefix('v1')->group(base_path('Modules/Cms/routes/api.php'));

// Affiliate module routes
Route::prefix('v1')->group(base_path('Modules/Affiliate/routes/api.php'));

// Vendor module routes (public vendor, vendor management, wallet, passwordless login)
Route::prefix('v1')->group(base_path('Modules/Vendor/routes/api.php'));

// Core module public routes (locations, currencies, languages)
Route::prefix('v1/core')->middleware('throttle:api')->group(base_path('Modules/Core/routes/api.php'));

// RBAC module routes (roles, permissions, user-role assignments)
Route::prefix('v1')->group(base_path('Modules/RBAC/routes/api.php'));

// Shipping module routes
Route::prefix('v1')->group(base_path('Modules/Shipping/routes/api.php'));

// Inventory module routes (vendor + admin)
Route::prefix('v1')->group(base_path('Modules/Inventory/routes/api.php'));

// Search module routes (public + admin)
Route::prefix('v1')->group(base_path('Modules/Search/routes/api.php'));

// Support module routes (FAQs, tickets, customer disputes, chat)
Route::prefix('v1')->group(base_path('Modules/Support/routes/api.php'));

// Main api.php (admin routes, cross-cutting concern routes)
Route::prefix('v1')->group(base_path('routes/api/v1/api.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/seo_and_extras.php'));

// Health check
Route::get('health', fn() => response()->json([
    'status'  => 'ok',
    'version' => 'v1',
    'time'    => now()->toDateTimeString(),
]));
