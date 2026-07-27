<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;

/*
|--------------------------------------------------------------------------
| Public CMS Routes
|--------------------------------------------------------------------------
*/

Route::prefix('cms')->middleware('throttle:api')->group(function () {
    Route::get('pages',              [CmsController::class, 'pages']);
    Route::get('pages/{slug}',       [CmsController::class, 'page']);
    Route::get('blocks',             [CmsController::class, 'blocks']);
    Route::get('blocks/{slug}',      [CmsController::class, 'block']);
    Route::get('menus',              [CmsController::class, 'menus']);
    Route::get('menus/{location}',   [CmsController::class, 'menu']);
    Route::get('banners/{pos}',      [CmsController::class, 'banners']);
    Route::get('homepage',           [CmsController::class, 'homepage']);
    Route::get('settings',           [CmsController::class, 'settings']);
});
