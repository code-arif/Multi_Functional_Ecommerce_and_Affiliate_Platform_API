<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\RecentlyViewedController;
use Modules\Cart\Http\Controllers\CompareController;

/*
|--------------------------------------------------------------------------
| Public Routes (guest accessible via X-Session-ID header)
|--------------------------------------------------------------------------
*/
Route::middleware('throttle:api')->group(function () {

    // Recently Viewed — accessible to guests
    Route::prefix('recently-viewed')->group(function () {
        Route::get('/',                  [RecentlyViewedController::class, 'index']);
        Route::post('{product}',         [RecentlyViewedController::class, 'track']);
    });

    // Compare List — accessible to guests
    Route::prefix('compare')->group(function () {
        Route::get('/',                  [CompareController::class, 'index']);
        Route::post('{product}',         [CompareController::class, 'add']);
        Route::delete('{product}',       [CompareController::class, 'remove']);
        Route::delete('/',               [CompareController::class, 'clear']);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'banned'])->group(function () {

    // Cart merge — merge guest cart into user cart on login
    Route::post('cart/merge', function (\Illuminate\Http\Request $request) {
        $service = app(\Modules\Cart\Services\CartService::class);
        $sessionId = $request->header('X-Session-ID');

        if (!$sessionId) {
            return response()->json(['success' => false, 'message' => 'No session ID provided.'], 400);
        }

        $cart = $service->mergeGuestCart($sessionId, $request->user());
        return response()->json([
            'success' => true,
            'message' => 'Cart merged successfully.',
            'data'    => new \Modules\Cart\Http\Resources\CartResource($cart),
        ]);
    });

    // Merge recently viewed from session to user
    Route::post('recently-viewed/merge', function (\Illuminate\Http\Request $request) {
        $service = app(\Modules\Cart\Services\RecentlyViewedService::class);
        $sessionId = $request->header('X-Session-ID');

        if (!$sessionId) {
            return response()->json(['success' => false, 'message' => 'No session ID provided.'], 400);
        }

        $service->mergeSessionIntoUser($sessionId, $request->user());
        return response()->json(['success' => true, 'message' => 'Recently viewed merged.']);
    });

    // Merge compare list from session to user
    Route::post('compare/merge', function (\Illuminate\Http\Request $request) {
        $service = app(\Modules\Cart\Services\CompareService::class);
        $sessionId = $request->header('X-Session-ID');

        if (!$sessionId) {
            return response()->json(['success' => false, 'message' => 'No session ID provided.'], 400);
        }

        $service->mergeSessionIntoUser($sessionId, $request->user());
        return response()->json(['success' => true, 'message' => 'Compare list merged.']);
    });
});
