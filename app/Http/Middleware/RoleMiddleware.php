<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * RoleMiddleware
 *
 * Usage in routes:
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,moderator')      // Any of these roles
 *   ->middleware('permission:products.create')
 */
class RoleMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse('Authentication required.');
        }

        // Load roles if not already loaded
        $user->loadMissing('roles');

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        Log::channel('security')->warning('Unauthorized role access attempt', [
            'user_id'        => $user->id,
            'required_roles' => $roles,
            'user_roles'     => $user->roles->pluck('name')->toArray(),
            'url'            => $request->fullUrl(),
            'ip'             => $request->ip(),
        ]);

        return $this->forbiddenResponse(
            'You do not have the required role to access this resource.'
        );
    }
}