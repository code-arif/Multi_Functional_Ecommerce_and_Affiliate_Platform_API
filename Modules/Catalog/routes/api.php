<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\BrandManageController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\CategoryManageController;
use Modules\Catalog\Http\Controllers\ProductController;
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


Route::middleware(['auth:sanctum', 'admin', 'banned'])->prefix('admin')->group(function () {

        // Categories Management
        Route::get('categories', [CategoryManageController::class, 'index'])->middleware('permission:categories.view');
        Route::post('categories/store', [CategoryManageController::class, 'store'])->middleware('permission:categories.manage');
        Route::put('categories/{category}/update', [CategoryManageController::class, 'update'])->middleware('permission:categories.manage');
        Route::delete('categories/{category}/delete', [CategoryManageController::class, 'destroy'])->middleware('permission:categories.manage');

        // Brands
        Route::get('brands', [BrandManageController::class, 'index'])->middleware('permission:brands.view');
        Route::post('brands/store', [BrandManageController::class, 'store'])->middleware('permission:brands.manage');
        Route::put('brands/{brand}/update', [BrandManageController::class, 'update'])->middleware('permission:brands.manage');
        Route::delete('brands/{brand}/delete', [BrandManageController::class, 'destroy'])->middleware('permission:brands.manage');
    });
