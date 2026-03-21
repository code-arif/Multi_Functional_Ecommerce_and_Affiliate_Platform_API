<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class AdminMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || (!$user->isAdmin() && !$user->isModerator())) {
            return $this->forbiddenResponse('Admin access required.');
        }

        return $next($request);
    }
}
