<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * ForceJsonResponse
 *
 * Forces the Accept: application/json header on all API requests.
 * This ensures Laravel returns JSON errors (401, 403, 404, 422)
 * instead of HTML redirect responses.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}
