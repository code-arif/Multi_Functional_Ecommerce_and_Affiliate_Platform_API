<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
        ModelNotFoundException::class,
        ThrottleRequestsException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log security-relevant exceptions
            if ($e instanceof ThrottleRequestsException) {
                Log::channel('security')->warning('Rate limit exceeded', [
                    'ip'  => request()->ip(),
                    'url' => request()->fullUrl(),
                ]);
            }
        });
    }

    public function render($request, Throwable $e)
    {
        // All API requests get JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException($request, Throwable $e)
    {
        // Validation errors — 422
        if ($e instanceof ValidationException) {
            return $this->errorResponse(
                'Validation failed.',
                $e->errors(),
                422
            );
        }

        // Model not found — 404
        if ($e instanceof ModelNotFoundException) {
            $model   = last(explode('\\', $e->getModel()));
            return $this->errorResponse(
                "{$model} not found.",
                null,
                404
            );
        }

        // Route not found — 404
        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse('Route not found.', null, 404);
        }

        // Method not allowed — 405
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse('HTTP method not allowed.', null, 405);
        }

        // Unauthenticated — 401
        if ($e instanceof AuthenticationException) {
            return $this->errorResponse('Unauthenticated. Please log in.', null, 401);
        }

        // Authorization — 403
        if ($e instanceof AuthorizationException) {
            return $this->errorResponse('You do not have permission to perform this action.', null, 403);
        }

        // Rate limit — 429
        if ($e instanceof ThrottleRequestsException) {
            return $this->errorResponse(
                'Too many requests. Please slow down.',
                ['retry_after' => $e->getHeaders()['Retry-After'] ?? 60],
                429
            );
        }

        // Generic server error — 500
        $message = config('app.debug')
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again.';

        Log::error('Unhandled API exception', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'url'     => request()->fullUrl(),
            'user_id' => request()->user()?->id,
        ]);

        return $this->errorResponse($message, null, 500);
    }
}
