<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware(['auth:sanctum', 'admin', 'banned'])->prefix('admin')->group(function () {
    Route::get('products', [ProductController::class, 'index'])->middleware('permission:products.view');
    Route::post('products', [ProductController::class, 'store'])->middleware('permission:products.create');
    Route::get('products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view');
    Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:products.edit');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');
    Route::post('products/upload-image', [ProductController::class, 'uploadImage'])->middleware('permission:products.create');
});
