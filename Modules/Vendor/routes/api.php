<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;
use Modules\Vendor\Http\Controllers\VendorDashboardController;
use Modules\Vendor\Http\Controllers\VendorProductController;
use Modules\Vendor\Http\Controllers\VendorOrderController;
use Modules\Vendor\Http\Controllers\VendorCouponController;
use Modules\Vendor\Http\Controllers\VendorReviewController;
use Modules\Vendor\Http\Controllers\VendorSettingsController;

/*
|--------------------------------------------------------------------------
| Vendor Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// Public vendor listing
Route::prefix('vendors')->middleware('throttle:api')->group(function () {
    Route::get('/',             [VendorController::class, 'index']);
    Route::get('{slug}',        [VendorController::class, 'show']);
});

// Vendor passwordless login (OTP-based) — rate-limited
Route::prefix('vendor/auth')->middleware('throttle:auth')->group(function () {
    Route::post('otp/send',     [VendorController::class, 'vendorOtpSend']);
    Route::post('otp/verify',   [VendorController::class, 'vendorOtpVerify']);
});

// Vendor registration (authenticated, but no vendor check needed yet)
Route::middleware(['auth:sanctum', 'banned'])->group(function () {
    Route::post('vendor/register',    [VendorController::class, 'register']);
});

// ─── Authenticated Vendor Operations ─────────────────────────────
// All routes protected by 'vendor' middleware (checks active vendor record)
Route::middleware(['auth:sanctum', 'vendor', 'banned'])->group(function () {

    // ─── Vendor Profile ───────────────────────────────────────
    Route::get('vendor/profile',            [VendorController::class, 'profile']);
    Route::put('vendor/profile',            [VendorController::class, 'updateProfile']);
    Route::get('vendor/documents',          [VendorController::class, 'documents']);
    Route::post('vendor/documents',         [VendorController::class, 'uploadDocument']);

    // ─── Vendor Dashboard ─────────────────────────────────────
    Route::get('vendor/dashboard',          [VendorDashboardController::class, 'index']);

    // ─── Vendor Products (vendor-scoped CRUD) ─────────────────
    Route::prefix('vendor/products')->group(function () {
        Route::get('/',                     [VendorProductController::class, 'index']);
        Route::post('/',                    [VendorProductController::class, 'store']);
        Route::get('{product}',             [VendorProductController::class, 'show']);
        Route::put('{product}',             [VendorProductController::class, 'update']);
        Route::delete('{product}',          [VendorProductController::class, 'destroy']);
    });

    // ─── Vendor Orders ────────────────────────────────────────
    Route::prefix('vendor/orders')->group(function () {
        Route::get('/',                     [VendorOrderController::class, 'index']);
        Route::get('{order}',               [VendorOrderController::class, 'show']);
        Route::patch('{order}/status',      [VendorOrderController::class, 'updateStatus']);
    });

    // ─── Vendor Coupons ───────────────────────────────────────
    Route::prefix('vendor/coupons')->group(function () {
        Route::get('/',                     [VendorCouponController::class, 'index']);
        Route::post('/',                    [VendorCouponController::class, 'store']);
        Route::put('{coupon}',              [VendorCouponController::class, 'update']);
        Route::delete('{coupon}',           [VendorCouponController::class, 'destroy']);
    });

    // ─── Vendor Reviews ───────────────────────────────────────
    Route::prefix('vendor/reviews')->group(function () {
        Route::get('/',                     [VendorReviewController::class, 'index']);
        Route::post('{review}/reply',       [VendorReviewController::class, 'reply']);
    });

    // ─── Vendor Settings ──────────────────────────────────────
    Route::prefix('vendor/settings')->group(function () {
        Route::get('/',                     [VendorSettingsController::class, 'index']);
        Route::post('/',                    [VendorSettingsController::class, 'update']);
    });
});
