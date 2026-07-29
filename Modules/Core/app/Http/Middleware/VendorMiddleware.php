<?php

namespace Modules\Core\Http\Middleware;

use Modules\Core\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class VendorMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorizedResponse('Authentication required.');
        }

        $vendor = $user->vendor;

        if (!$vendor) {
            return $this->forbiddenResponse('You are not registered as a vendor.');
        }

        if ($vendor->status !== 'active') {
            return $this->forbiddenResponse('Your vendor account is not active.');
        }

        return $next($request);
    }
}
