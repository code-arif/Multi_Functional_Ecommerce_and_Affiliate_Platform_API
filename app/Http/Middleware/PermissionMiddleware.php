<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PermissionMiddleware
 *
 * Usage:
 *   ->middleware('permission:products.create')
 *   ->middleware('permission:orders.view,orders.manage')  // Any of these
 */
class PermissionMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse();
        }

        $user->loadMissing('roles.permissions');

        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
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