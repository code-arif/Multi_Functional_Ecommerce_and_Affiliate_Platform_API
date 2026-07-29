<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;

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

// Authenticated vendor routes
Route::middleware(['auth:sanctum', 'banned'])->group(function () {
    // Vendor management
    Route::post('vendor/register',    [VendorController::class, 'register']);
    Route::get('vendor/profile',      [VendorController::class, 'profile']);
    Route::put('vendor/profile',      [VendorController::class, 'updateProfile']);
    Route::post('vendor/documents',   [VendorController::class, 'uploadDocument']);

    // Vendor Wallet & Payouts
    Route::prefix('vendor/wallet')->group(function () {
        Route::get('/',                   [VendorController::class, 'wallet']);
        Route::get('transactions',        [VendorController::class, 'walletTransactions']);
        Route::get('stats',               [VendorController::class, 'walletStats']);
        Route::post('payouts',            [VendorController::class, 'requestPayout']);
        Route::get('payouts',             [VendorController::class, 'payouts']);
    });
});
