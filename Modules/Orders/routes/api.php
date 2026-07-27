<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\VendorOrderController;

/*
|--------------------------------------------------------------------------
| Vendor Order Routes (auth:sanctum + vendor role)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'banned'])->prefix('vendor')->group(function () {

    Route::prefix('orders')->group(function () {
        Route::get('/',                       [VendorOrderController::class, 'index']);
        Route::get('{order}',                 [VendorOrderController::class, 'show']);
        Route::patch('{order}/status',         [VendorOrderController::class, 'updateStatus']);
    });
});
