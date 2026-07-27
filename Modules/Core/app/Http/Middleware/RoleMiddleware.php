<?php

namespace Modules\Core\Http\Middleware;

use Modules\Core\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Authentication required.', null, 401);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        Log::channel('security')->warning('Unauthorized role access attempt', [
            'user_id'        => $user->id,
            'required_roles' => $roles,
            'url'            => $request->fullUrl(),
            'ip'             => $request->ip(),
        ]);

        return $this->forbiddenResponse(
            'You do not have the required role to access this resource.'
        );
    }
}
