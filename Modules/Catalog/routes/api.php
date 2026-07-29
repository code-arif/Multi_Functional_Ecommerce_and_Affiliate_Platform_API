<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Reviews\Http\Controllers\ReviewController;

/*
|--------------------------------------------------------------------------
| Catalog Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// Products — public browsing
Route::prefix('products')->middleware('throttle:api')->group(function () {
    Route::get('/',                     [ProductController::class, 'index']);
    Route::get('featured',              [ProductController::class, 'featured']);
    Route::get('new-arrivals',          [ProductController::class, 'newArrivals']);
    Route::get('bestsellers',           [ProductController::class, 'bestsellers']);
    Route::get('{slug}/reviews/stats',  [ReviewController::class, 'stats']);
    Route::get('{slug}/reviews',        [ReviewController::class, 'index']);
    Route::get('{slug}/related',        [ProductController::class, 'related']);
    Route::get('{slug}',                [ProductController::class, 'show']);
});

// Categories
Route::prefix('categories')->middleware('throttle:api')->group(function () {
    Route::get('/',             [CategoryController::class, 'index']);
    Route::get('{slug}',        [CategoryController::class, 'show']);
});
