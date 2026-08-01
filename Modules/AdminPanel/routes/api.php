<?php

use Illuminate\Support\Facades\Route;
use Modules\AdminPanel\Http\Controllers\Vendor\VendorManageController;

// ADMIN ROUTES  — auth:sanctum + admin middleware
Route::middleware(['auth:sanctum', 'admin', 'banned'])->prefix('admin')->group(function () {
    // Vendor Management
    Route::prefix('vendors')->middleware('permission:vendors.view')->group(function () {
        Route::get('/', [VendorManageController::class, 'index']);
        Route::post('/store', [VendorManageController::class, 'store'])->middleware('permission:vendors.create');
        Route::get('pending', [VendorManageController::class, 'pending']);
        Route::get('{vendor}', [VendorManageController::class, 'show']);
        Route::post('{vendor}/approve', [VendorManageController::class, 'approve'])->middleware('permission:vendors.approve');
        Route::post('{vendor}/reject', [VendorManageController::class, 'reject'])->middleware('permission:vendors.approve');
        Route::post('{vendor}/suspend', [VendorManageController::class, 'suspend'])->middleware('permission:vendors.manage');
        Route::post('documents/{document}/verify', [VendorManageController::class, 'verifyDocument'])->middleware('permission:vendors.manage');
        Route::post('documents/{document}/reject', [VendorManageController::class, 'rejectDocument'])->middleware('permission:vendors.manage');
    });
});
