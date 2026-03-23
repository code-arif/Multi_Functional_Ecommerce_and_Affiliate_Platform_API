<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BannedUserMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->status === 'banned') {
            Log::channel('security')->warning('Banned user attempted access', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'ip'      => $request->ip(),
            ]);

            // Revoke all tokens for banned users
            $user->tokens()->delete();

            return $this->errorResponse(
                'Your account has been suspended. Please contact support.',
                null,
                403
            );
        }

        if ($user && $user->status === 'inactive') {
            return $this->errorResponse(
                'Your account is inactive. Please contact support.',
                null,
                403
            );
        }

        return $next($request);
    }
}
