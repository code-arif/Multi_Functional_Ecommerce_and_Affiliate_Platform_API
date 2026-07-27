<?php

namespace Modules\Core\Http\Middleware;

use Modules\Core\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermissionMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            return $this->errorResponse('Authentication required.', null, 401);
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        // Super-admin bypass
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        Log::channel('security')->warning('Unauthorized permission access', [
            'user_id'     => $user->id,
            'permissions' => $permissions,
            'url'         => $request->fullUrl(),
        ]);

        return $this->forbiddenResponse(
            'You do not have permission to perform this action.'
        );
    }
}
