<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymentController;
use Modules\Payments\Http\Controllers\AdminRefundController;

/*
|--------------------------------------------------------------------------
| Authenticated Payment Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'banned'])->group(function () {

    // Payment processing
    Route::post('payments/process', [PaymentController::class, 'process']);

    // Saved payment methods
    Route::prefix('payments/methods')->group(function () {
        Route::get('/',                   [PaymentController::class, 'methods']);
        Route::post('/',                  [PaymentController::class, 'storeMethod']);
        Route::delete('{method}',         [PaymentController::class, 'destroyMethod']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Payment Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin', 'banned'])
    ->prefix('admin')
    ->group(function () {

        Route::post('payments/{payment}/refund', [AdminRefundController::class, 'refund'])
            ->middleware('permission:payments.refund');

        Route::get('orders/{order}/payments', [AdminRefundController::class, 'orderPayment'])
            ->middleware('permission:payments.view');
    });
