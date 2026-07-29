<?php

use Illuminate\Support\Facades\Route;
use Modules\Checkout\Http\Controllers\CheckoutController;

/*
|--------------------------------------------------------------------------
| Checkout Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// Checkout — stricter throttle to prevent order flooding
Route::prefix('checkout')->middleware('throttle:checkout')->group(function () {
    Route::post('/', [CheckoutController::class, 'process']);
    Route::get('shipping-cost', [CheckoutController::class, 'shippingCost']);
});

// Checkout — authenticated routes (preview, shipping options, tax calculation)
Route::middleware(['auth:sanctum', 'banned'])->prefix('checkout')->group(function () {
    Route::post('preview',           [CheckoutController::class, 'preview']);
    Route::get('shipping-options',   [CheckoutController::class, 'shippingOptions']);
    Route::post('calculate-tax',     [CheckoutController::class, 'calculateTax']);
});
