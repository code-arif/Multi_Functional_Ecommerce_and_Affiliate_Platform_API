<?php

use Illuminate\Support\Facades\Route;
use Modules\Promotions\Http\Controllers\PromotionController;
use Modules\Promotions\Http\Controllers\AdminPromotionController;

/*
|--------------------------------------------------------------------------
| Public Promotion Routes
|--------------------------------------------------------------------------
*/

Route::prefix('promotions')->middleware('throttle:api')->group(function () {
    Route::get('/',            [PromotionController::class, 'index']);
    Route::get('flash-sales',  [PromotionController::class, 'flashSales']);
    Route::get('upcoming',     [PromotionController::class, 'upcoming']);
});

/*
|--------------------------------------------------------------------------
| Admin Promotion Routes
|--------------------------------------------------------------------------
|
| Registered in main routes/api/v1/api.php under admin middleware.
|
*/
