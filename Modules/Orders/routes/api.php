<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Admin\CancelRequestController as AdminCancelRequestController;
use Modules\Orders\Http\Controllers\Admin\OrderController as AdminOrderController;
use Modules\Orders\Http\Controllers\Customer\CancelRequestController as CustomerCancelRequestController;
use Modules\Orders\Http\Controllers\Customer\OrderController as CustomerOrderController;
use Modules\Orders\Http\Controllers\Customer\TrackingController;
use Modules\Orders\Http\Controllers\Vendor\OrderController as VendorOrderController;

/*
|--------------------------------------------------------------------------
| Orders Module API Routes
| Registered automatically via the module RouteServiceProvider under /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ─────────────────────────────────────────────────────────────────────────
    // PUBLIC — guest order tracking by token
    // ─────────────────────────────────────────────────────────────────────────

    Route::get('orders/track/{token}', [TrackingController::class, 'trackGuest']);

    // ─────────────────────────────────────────────────────────────────────────
    // CUSTOMER — authenticated shopper orders
    // ─────────────────────────────────────────────────────────────────────────

    Route::middleware(['auth:sanctum', 'banned'])->prefix('orders')->group(function () {
        Route::get('/',                     [CustomerOrderController::class, 'index']);
        Route::get('summary',               [CustomerOrderController::class, 'summary']);
        Route::get('{orderNumber}',         [CustomerOrderController::class, 'show']);
        Route::get('{orderNumber}/status-history', [CustomerOrderController::class, 'statusHistory']);
        Route::get('{orderNumber}/invoice', [CustomerOrderController::class, 'invoice']);
        Route::post('{orderNumber}/cancel-request', [CustomerCancelRequestController::class, 'store']);
    });

    // ─────────────────────────────────────────────────────────────────────────
    // VENDOR — vendor shop order management (scoped to own shop)
    // ─────────────────────────────────────────────────────────────────────────

    Route::middleware(['auth:sanctum', 'vendor', 'banned'])->prefix('vendor/orders')->group(function () {
        Route::get('/',                  [VendorOrderController::class, 'index']);
        Route::get('stats',              [VendorOrderController::class, 'stats']);
        Route::get('{order}',            [VendorOrderController::class, 'show']);
        Route::patch('{order}/status',   [VendorOrderController::class, 'updateStatus']);
        Route::patch('{order}/tracking', [VendorOrderController::class, 'updateTracking']);
        Route::get('{order}/invoice',    [VendorOrderController::class, 'invoice']);
    });

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN / SUPER ADMIN — platform-wide order management & monitoring
    // ─────────────────────────────────────────────────────────────────────────

    Route::middleware(['auth:sanctum', 'admin', 'banned'])->prefix('admin')->group(function () {
        Route::prefix('orders')->middleware('permission:orders.view')->group(function () {
            Route::get('/',                 [AdminOrderController::class, 'index']);
            Route::get('stats',             [AdminOrderController::class, 'stats']);
            Route::get('audit',             [AdminOrderController::class, 'audit'])->middleware('permission:logs.view');
            Route::get('{order}',           [AdminOrderController::class, 'show']);
            Route::patch('{order}/status',  [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.manage');
            Route::patch('{order}/note',    [AdminOrderController::class, 'updateAdminNote'])->middleware('permission:orders.manage');
            Route::post('{order}/refund',   [AdminOrderController::class, 'refund'])->middleware('permission:orders.refund');
            Route::get('{order}/invoice',   [AdminOrderController::class, 'invoice']);
        });

        Route::prefix('cancel-requests')->middleware('permission:orders.manage')->group(function () {
            Route::get('/',             [AdminCancelRequestController::class, 'index']);
            Route::get('{cancelRequest}', [AdminCancelRequestController::class, 'show']);
            Route::patch('{cancelRequest}', [AdminCancelRequestController::class, 'review']);
        });
    });
});
