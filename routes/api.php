<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(base_path('routes/api/v1/api.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/seo_and_extras.php'));

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

// Health check
Route::get('health', fn() => response()->json([
    'status'  => 'ok',
    'version' => 'v1',
    'time'    => now()->toDateTimeString(),
]));
