<?php

use Illuminate\Support\Facades\Route;
use Modules\Affiliate\Http\Controllers\AffiliateController;

/*
|--------------------------------------------------------------------------
| Affiliate Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('affiliate')->middleware('throttle:api')->group(function () {
    Route::get('/',             [AffiliateController::class, 'index']);
    Route::get('{slug}',        [AffiliateController::class, 'show']);
    Route::post('{slug}/click', [AffiliateController::class, 'click']);
});
