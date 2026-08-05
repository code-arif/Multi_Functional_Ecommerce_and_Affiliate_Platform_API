<?php

use App\Http\Controllers\API\V1\Cms\SeoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SEO Routes — Slug-based meta data for every entity type
|--------------------------------------------------------------------------
| These are consumed by the Vue.js SPA to set <head> meta tags
| dynamically via vue-meta or @unhead/vue.
|
| Example: GET /api/v1/seo/product/iphone-15-pro
|          GET /api/v1/seo/category/mobile-phones
|--------------------------------------------------------------------------
*/

Route::prefix('seo')->group(function () {
    Route::get('homepage',         [SeoController::class, 'homepage']);
    Route::get('product/{slug}',   [SeoController::class, 'product']);
    Route::get('category/{slug}',  [SeoController::class, 'category']);
    Route::get('page/{slug}',      [SeoController::class, 'page']);
});

// Sitemap — XML response
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])
     ->name('sitemap');
