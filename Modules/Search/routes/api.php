<?php

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\SearchController;
use Modules\Search\Http\Controllers\AdminSearchController;

/*
|--------------------------------------------------------------------------
| Search Module API Routes
|--------------------------------------------------------------------------
|
| Public search endpoints (via Catalog SearchController) and admin
| analytics/reindex management.
|
| NOTE: Public search endpoints (/, suggestions, price-range, facets)
| are registered in routes/api/v1/api.php via Catalog\SearchController
| which already uses the ES-backed SearchService. Only additional
| endpoints are added here.
|
*/

// ─── Public Search Routes (additional) ──────────────────────────────

Route::get('search/popular', [SearchController::class, 'popularSearches'])
    ->name('public.search.popular');

// ─── Admin Search Routes ────────────────────────────────────────────

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
