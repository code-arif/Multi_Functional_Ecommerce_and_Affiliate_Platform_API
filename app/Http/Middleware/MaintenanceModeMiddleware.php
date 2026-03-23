<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class MaintenanceModeMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        // Admin users bypass maintenance mode
        if ($request->user()?->isAdmin()) {
            return $next($request);
        }

        $maintenance = Setting::get('maintenance_mode', 'false');

        if ($maintenance === 'true' || $maintenance === true) {
            return $this->errorResponse(
                'The store is temporarily under maintenance. Please check back soon.',
                ['eta' => Setting::get('maintenance_eta', null)],
                503
            );
        }

        return $next($request);
    }
}
