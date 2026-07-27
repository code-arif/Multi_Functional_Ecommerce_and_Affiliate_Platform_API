<?php

namespace Modules\Core\Http\Middleware;

use Modules\Core\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;

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
