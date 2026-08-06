<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;
use Modules\Vendor\Http\Controllers\Admin\VendorManageController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('vendors', VendorController::class)->names('vendor');
});

// Admin-facing Vendor Management Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('v1/admin')->group(function () {
    Route::prefix('vendors')->middleware('permission:vendors.view')->group(function () {
        Route::get('/', [VendorManageController::class, 'index']); // DONE: Vendor List
        Route::post('/store', [VendorManageController::class, 'store'])->middleware('permission:vendors.create'); // DONE: Vendor Store
        // Route::get('pending', [VendorManageController::class, 'pending']);
        Route::get('{uuid}', [VendorManageController::class, 'show']); // DONE: Vendor Details
        Route::post('{uuid}/approve', [VendorManageController::class, 'approve'])->middleware('permission:vendors.approve'); // DONE: Vendor Approve
        Route::post('{uuid}/reject', [VendorManageController::class, 'reject'])->middleware('permission:vendors.approve'); // DONE: Vendor Reject
        Route::post('{uuid}/suspend', [VendorManageController::class, 'suspend'])->middleware('permission:vendors.manage'); // DONE: Vendor Suspend
        Route::post('documents/{document}/verify', [VendorManageController::class, 'verifyDocument'])->middleware('permission:vendors.manage');
        Route::post('documents/{document}/reject', [VendorManageController::class, 'rejectDocument'])->middleware('permission:vendors.manage');
    });
});
