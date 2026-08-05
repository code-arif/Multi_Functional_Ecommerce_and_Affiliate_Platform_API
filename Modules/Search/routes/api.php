<?php

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\SearchController;
use Modules\Search\Http\Controllers\AdminSearchController;

/*
|--------------------------------------------------------------------------
| Search Module API Routes
|--------------------------------------------------------------------------
|
| All search endpoints consolidated in the Search module:
| - Public search (/, suggestions, price-range, facets, popular)
| - Admin analytics, reindex, status
|
| NOTE: The old `routes/api/v1/api.php` still references SearchController
| for backwards compatibility but now points to this module's controller.
|
*/

// Public Search Routes

Route::prefix('search')->middleware('throttle:search')->group(function () {
    Route::get('/', [SearchController::class, 'search'])->name('public.search');
    Route::get('suggestions', [SearchController::class, 'suggestions'])->name('public.search.suggestions');
    Route::get('price-range', [SearchController::class, 'priceRange'])->name('public.search.price-range');
    Route::get('facets', [SearchController::class, 'facets'])->name('public.search.facets');
    Route::get('popular', [SearchController::class, 'popularSearches'])->name('public.search.popular');
});

// Admin Search Routes

Route::prefix('admin/search')->middleware(['auth:sanctum', 'permission:search.view'])->group(function () {
    Route::get('analytics', [AdminSearchController::class, 'analytics'])->name('admin.search.analytics');
    Route::get('popular', [AdminSearchController::class, 'popularSearches'])->name('admin.search.popular');
    Route::get('zero-results', [AdminSearchController::class, 'zeroResultSearches'])->name('admin.search.zero-results');
    Route::get('status', [AdminSearchController::class, 'status'])->name('admin.search.status');
});

// Reindex requires manage permission
Route::post('admin/search/reindex', [AdminSearchController::class, 'reindex'])
    ->middleware(['auth:sanctum', 'permission:search.manage'])
    ->name('admin.search.reindex');
