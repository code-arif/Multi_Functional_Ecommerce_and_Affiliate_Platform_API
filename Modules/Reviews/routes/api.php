<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\ReviewController;

/*
|--------------------------------------------------------------------------
| Reviews Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'banned'])->group(function () {
    // Reviews — throttled to prevent spam
    Route::post('reviews', [ReviewController::class, 'store'])
        ->middleware('throttle:10,1');
});
