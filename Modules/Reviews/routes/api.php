<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\ReviewController;
use Modules\Reviews\Http\Controllers\VendorReviewController;

/*
|--------------------------------------------------------------------------
| Review Routes
|--------------------------------------------------------------------------
|
| Public: product reviews listing + stats (handled in main routes/api/v1/api.php)
| Auth: create, my reviews, update, delete, helpful votes
| Vendor: list reviews + respond
|
*/

// Authenticated user review routes
Route::middleware(['auth:sanctum', 'banned'])->group(function () {
    // Submit a review
    Route::post('reviews', [ReviewController::class, 'store'])->middleware('throttle:10,1');

    // My reviews
    Route::get('reviews/mine', [ReviewController::class, 'myReviews']);

    // Update/delete own review
    Route::put('reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);

    // Helpful votes
    Route::post('reviews/{review}/helpful', [ReviewController::class, 'helpful']);
    Route::delete('reviews/{review}/helpful', [ReviewController::class, 'unhelpful']);
});

// Vendor review routes
Route::middleware(['auth:sanctum', 'banned'])
    ->prefix('vendor')
    ->group(function () {
        Route::get('reviews', [VendorReviewController::class, 'index']);
        Route::post('reviews/{review}/respond', [VendorReviewController::class, 'respond']);
    });

/*
|--------------------------------------------------------------------------
| Admin review routes (registered in main routes/api/v1/api.php)
|--------------------------------------------------------------------------
|
| GET    /admin/reviews                     — AdminReviewController@index
| POST   /admin/reviews/{review}/approve    — AdminReviewController@approve
| POST   /admin/reviews/{review}/reject     — AdminReviewController@reject
| DELETE /admin/reviews/{review}            — AdminReviewController@destroy
|
*/
