<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\VendorFinanceController;
use Modules\Finance\Http\Controllers\AdminFinanceController;

/*
|--------------------------------------------------------------------------
| Vendor Finance Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'banned'])->prefix('vendor/finance')->group(function () {
    Route::get('wallet',           [VendorFinanceController::class, 'wallet']);
    Route::get('transactions',     [VendorFinanceController::class, 'transactions']);
    Route::get('commissions',      [VendorFinanceController::class, 'commissions']);
    Route::post('payouts',         [VendorFinanceController::class, 'requestPayout']);
    Route::get('payouts',          [VendorFinanceController::class, 'payouts']);
    Route::get('settlements',      [VendorFinanceController::class, 'settlements']);
});

/*
|--------------------------------------------------------------------------
| Admin Finance Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin', 'banned'])
    ->prefix('admin')
    ->group(function () {

        Route::prefix('finance')->group(function () {
            // Commissions
            Route::get('commissions',                [AdminFinanceController::class, 'commissions'])
                ->middleware('permission:finance.view');
            Route::post('commissions/{commission}/approve', [AdminFinanceController::class, 'approveCommission'])
                ->middleware('permission:finance.manage');

            // Payouts
            Route::get('payouts',                    [AdminFinanceController::class, 'payouts'])
                ->middleware('permission:finance.view');
            Route::post('payouts/{payout}/approve',    [AdminFinanceController::class, 'approvePayout'])
                ->middleware('permission:finance.manage');
            Route::post('payouts/{payout}/reject',     [AdminFinanceController::class, 'rejectPayout'])
                ->middleware('permission:finance.manage');
            Route::post('payouts/{payout}/complete',   [AdminFinanceController::class, 'completePayout'])
                ->middleware('permission:finance.manage');

            // Settlements
            Route::get('settlements',                [AdminFinanceController::class, 'settlements'])
                ->middleware('permission:finance.view');
            Route::post('settlements/generate',       [AdminFinanceController::class, 'generateSettlement'])
                ->middleware('permission:finance.manage');
            Route::post('settlements/{settlement}/finalize', [AdminFinanceController::class, 'finalizeSettlement'])
                ->middleware('permission:finance.manage');
        });
    });
