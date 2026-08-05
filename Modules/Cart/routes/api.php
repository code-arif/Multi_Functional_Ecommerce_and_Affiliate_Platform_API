<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\CartController;
use Modules\Cart\Http\Controllers\WishlistController;


/*
|--------------------------------------------------------------------------
| Cart Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// Cart — accessible to guests via X-Session-ID header
Route::prefix('cart')->middleware('throttle:api')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('items', [CartController::class, 'addItem']);
    Route::put('items/{item}', [CartController::class, 'updateItem']);
    Route::delete('items/{item}', [CartController::class, 'removeItem']);
    Route::delete('/', [CartController::class, 'clear']);
    Route::post('coupon', [CartController::class, 'applyCoupon']);
    Route::delete('coupon', [CartController::class, 'removeCoupon']);
});

Route::middleware(['auth:sanctum', 'banned'])->group(function () {

    // Wishlist
    Route::get('wishlist', [WishlistController::class, 'index']);
    Route::post('wishlist', [WishlistController::class, 'toggle']);
    Route::post('wishlist/move-to-cart', [WishlistController::class, 'moveToCart']);
});
