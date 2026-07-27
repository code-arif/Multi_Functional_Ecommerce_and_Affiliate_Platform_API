<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;
use Modules\Core\Http\Controllers\LocationController;
use Modules\Core\Http\Controllers\CurrencyController;
use Modules\Core\Http\Controllers\LanguageController;
use Modules\Core\Http\Controllers\MediaController;
use Modules\Core\Http\Controllers\ActivityLogController;

/*
|--------------------------------------------------------------------------
| Core Module API Routes
|
| Prefix: api/v1/core
| Middleware: throttle:api
|--------------------------------------------------------------------------
*/

// ─── Public Routes ─────────────────────────────────────────────────

// Locations (country/state/city data)
Route::get('countries',             [LocationController::class, 'countries']);
Route::get('countries/{country}',   [LocationController::class, 'countryShow']);
Route::get('countries/{country}/states', [LocationController::class, 'countryStates']);
Route::get('states/{state}',        [LocationController::class, 'stateShow']);
Route::get('states/{state}/cities', [LocationController::class, 'stateCities']);
Route::get('cities/{city}',         [LocationController::class, 'cityShow']);

// Currencies & Languages (read-only public)
Route::get('currencies',            [CurrencyController::class, 'index']);
Route::get('languages',             [LanguageController::class, 'index']);

// App info
Route::get('info',                  [CoreController::class, 'info']);

// ─── Authenticated Routes ──────────────────────────────────────────

Route::middleware(['auth:sanctum'])->group(function () {

    // Media library — requires auth
    Route::get('media',                     [MediaController::class, 'index']);
    Route::post('media/upload',             [MediaController::class, 'upload']);
    Route::delete('media/{medium}',         [MediaController::class, 'destroy']);

    // Currencies — write operations require admin
    Route::post('currencies',               [CurrencyController::class, 'store'])
        ->middleware('admin');
    Route::put('currencies/{currency}',     [CurrencyController::class, 'update'])
        ->middleware('admin');
    Route::delete('currencies/{currency}',  [CurrencyController::class, 'destroy'])
        ->middleware('admin');

    // Languages — write operations require admin
    Route::post('languages',                [LanguageController::class, 'store'])
        ->middleware('admin');
    Route::put('languages/{language}',      [LanguageController::class, 'update'])
        ->middleware('admin');
    Route::delete('languages/{language}',   [LanguageController::class, 'destroy'])
        ->middleware('admin');

    // Activity Logs — admin only
    Route::get('admin/currencies',          [CurrencyController::class, 'adminIndex'])
        ->middleware('admin');
    Route::get('admin/languages',           [LanguageController::class, 'adminIndex'])
        ->middleware('admin');
    Route::get('admin/logs',                [ActivityLogController::class, 'index'])
        ->middleware('admin');
    Route::get('admin/logs/{activityLog}',  [ActivityLogController::class, 'show'])
        ->middleware('admin');
});
