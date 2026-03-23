<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(base_path('routes/api/v1/api.php'));
Route::prefix('v1')->group(base_path('routes/api/v1/seo_and_extras.php'));

// Health check
Route::get('health', fn() => response()->json([
    'status'  => 'ok',
    'version' => 'v1',
    'time'    => now()->toDateTimeString(),
]));
