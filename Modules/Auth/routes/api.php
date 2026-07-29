<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\AddressController;
use Modules\Auth\Http\Controllers\OtpController;
use Modules\Auth\Http\Controllers\PasswordResetController;
use Modules\Auth\Http\Controllers\EmailVerificationController;
use Modules\Auth\Http\Controllers\DeviceController;

/*
|--------------------------------------------------------------------------
| Auth Module API Routes
|
| Prefix: api/v1/auth
|--------------------------------------------------------------------------
*/

// ─── Public Auth Routes (no authentication required) ────────────
// Rate-limited to prevent brute force attacks
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    // Registration & Login
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Password-based admin login
    Route::post('admin/login', [AuthController::class, 'adminLogin']);

    // Password-based vendor login
    Route::post('vendor/login', [AuthController::class, 'vendorLogin']);

    // Passwordless admin login (OTP-based)
    Route::post('admin/otp/send',   [AuthController::class, 'adminOtpSend']);
    Route::post('admin/otp/verify', [AuthController::class, 'adminOtpVerify']);

    // Password Reset
    Route::post('password/forgot', [PasswordResetController::class, 'forgot']);
    Route::post('password/reset',  [PasswordResetController::class, 'reset']);

    // OTP (no auth required for password reset flow)
    Route::post('otp/send',   [OtpController::class, 'send']);
    Route::post('otp/verify', [OtpController::class, 'verify']);
});

// ─── Authenticated User Routes ──────────────────────────────────
Route::middleware(['auth:sanctum', 'banned'])->group(function () {
    // Auth — Profile & Session
    Route::post('auth/logout',         [AuthController::class, 'logout']);
    Route::post('auth/logout-all',     [AuthController::class, 'logoutAll']);
    Route::get('auth/me',              [AuthController::class, 'me']);
    Route::put('auth/profile',         [AuthController::class, 'updateProfile']);
    Route::post('auth/avatar',         [AuthController::class, 'updateAvatar']);

    // Email Verification
    Route::post('auth/email/verify/send', [EmailVerificationController::class, 'sendVerification']);
    Route::post('auth/email/verify',      [EmailVerificationController::class, 'verify']);
    Route::get('auth/email/status',       [EmailVerificationController::class, 'status']);

    // Password Change
    Route::post('auth/password/change', [PasswordResetController::class, 'change']);

    // Device Management
    Route::get('auth/devices',                [DeviceController::class, 'index']);
    Route::delete('auth/devices',             [DeviceController::class, 'revokeAll']);
    Route::post('auth/devices/{device}/trust', [DeviceController::class, 'trust']);
    Route::delete('auth/devices/{device}',    [DeviceController::class, 'destroy']);

    // Addresses
    Route::apiResource('addresses', AddressController::class);
});
