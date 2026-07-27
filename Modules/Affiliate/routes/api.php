<?php

use Illuminate\Support\Facades\Route;
use Modules\Affiliate\Http\Controllers\AffiliateController;
use Modules\Affiliate\Http\Controllers\AdminAffiliateController;

/*
|--------------------------------------------------------------------------
| Public Affiliate Routes
|--------------------------------------------------------------------------
*/
Route::prefix('affiliate')->middleware('throttle:api')->group(function () {
    Route::get('/',             [AffiliateController::class, 'index']);
    Route::get('{slug}',        [AffiliateController::class, 'show']);
    Route::post('{slug}/click', [AffiliateController::class, 'click']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Affiliate Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'banned'])
    ->prefix('affiliate')
    ->group(function () {
        Route::get('dashboard', [AffiliateController::class, 'dashboard']);
        Route::get('earnings',  [AffiliateController::class, 'earnings']);
    });

/*
|--------------------------------------------------------------------------
| Admin Affiliate Routes (registered in main api.php)
|--------------------------------------------------------------------------
*/
