<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\BrandManageController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\CategoryManageController;
/*
|--------------------------------------------------------------------------
| Catalog Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// V1 block
Route::prefix('v1')->group(function () {

    // Categories
    Route::prefix('categories')->middleware('throttle:api')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('{slug}', [CategoryController::class, 'show']);
    });


    Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
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
});
