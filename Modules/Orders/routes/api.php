<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Orders Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// Guest order tracking (no auth — uses token)
Route::get('orders/track/{token}', [OrderController::class, 'trackGuest'])
    ->middleware('throttle:api');

// Authenticated order routes
Route::middleware(['auth:sanctum', 'banned'])->group(function () {
    Route::get('orders',                [OrderController::class, 'index']);
    Route::get('orders/{number}',       [OrderController::class, 'show']);
    Route::post('orders/{number}/cancel', [OrderController::class, 'cancel']);
});
