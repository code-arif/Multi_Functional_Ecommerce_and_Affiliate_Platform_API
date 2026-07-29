<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\CartController;

/*
|--------------------------------------------------------------------------
| Cart Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// Cart — accessible to guests via X-Session-ID header
Route::prefix('cart')->middleware('throttle:api')->group(function () {
    Route::get('/',                [CartController::class, 'index']);
    Route::post('items',           [CartController::class, 'addItem']);
    Route::put('items/{item}',     [CartController::class, 'updateItem']);
    Route::delete('items/{item}',  [CartController::class, 'removeItem']);
    Route::delete('/',             [CartController::class, 'clear']);
    Route::post('coupon',          [CartController::class, 'applyCoupon']);
    Route::delete('coupon',        [CartController::class, 'removeCoupon']);
});
