<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\WarehouseController;
use Modules\Inventory\Http\Controllers\VendorInventoryController;
use Modules\Inventory\Http\Controllers\AdminInventoryController;

/*
|--------------------------------------------------------------------------
| Vendor Inventory Routes (auth:sanctum + vendor role)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'banned'])->prefix('vendor')->group(function () {

    // Warehouses
    Route::prefix('warehouses')->group(function () {
        Route::get('/',                [WarehouseController::class, 'index']);
        Route::post('/',               [WarehouseController::class, 'store']);
        Route::get('{warehouse}',      [WarehouseController::class, 'show']);
        Route::put('{warehouse}',      [WarehouseController::class, 'update']);
        Route::delete('{warehouse}',   [WarehouseController::class, 'destroy']);
    });

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::get('/',                  [VendorInventoryController::class, 'index']);
        Route::get('summary',           [VendorInventoryController::class, 'summary']);
        Route::get('low-stock',         [VendorInventoryController::class, 'lowStock']);
        Route::get('out-of-stock',      [VendorInventoryController::class, 'outOfStock']);
        Route::post('adjust',           [VendorInventoryController::class, 'adjust']);
        Route::get('logs',              [VendorInventoryController::class, 'logs']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Inventory Routes (auth:sanctum + admin + permissions)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin', 'banned'])
    ->prefix('admin')
    ->group(function () {

        Route::prefix('inventory')->group(function () {
            Route::get('products',        [AdminInventoryController::class, 'products'])
                ->middleware('permission:inventory.view');
            Route::get('logs',            [AdminInventoryController::class, 'logs'])
                ->middleware('permission:inventory.view');
            Route::get('summary',         [AdminInventoryController::class, 'summary'])
                ->middleware('permission:inventory.view');
        });
    });
