<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;
use Modules\Cms\Http\Controllers\SeoController;

/*
|--------------------------------------------------------------------------
| CMS Module API Routes
| Prefix: api/v1
|--------------------------------------------------------------------------
*/

// CMS — public content
Route::prefix('cms')->middleware('throttle:api')->group(function () {
    Route::get('pages',             [CmsController::class, 'pages']);
    Route::get('pages/{slug}',      [CmsController::class, 'page']);
    Route::get('banners/{pos}',     [CmsController::class, 'banners']);
    Route::get('homepage',          [CmsController::class, 'homepage']);
    Route::get('settings',          [CmsController::class, 'settings']);
});

// SEO
Route::prefix('seo')->middleware('throttle:api')->group(function () {
    Route::get('homepage',          [SeoController::class, 'homepage']);
    Route::get('product/{slug}',    [SeoController::class, 'product']);
    Route::get('category/{slug}',   [SeoController::class, 'category']);
    Route::get('page/{slug}',       [SeoController::class, 'page']);
});

Route::get('sitemap.xml',           [SeoController::class, 'sitemap']);
