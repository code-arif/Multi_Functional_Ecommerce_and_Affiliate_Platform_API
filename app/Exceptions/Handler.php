<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return match(true) {
                $e instanceof ValidationException =>
                    $this->errorResponse('Validation failed', $e->errors(), 422),

                $e instanceof ModelNotFoundException =>
                    $this->errorResponse('Resource not found', null, 404),

                $e instanceof AuthenticationException =>
                    $this->errorResponse('Unauthenticated', null, 401),

                $e instanceof NotFoundHttpException =>
                    $this->errorResponse('Route not found', null, 404),

                $e instanceof MethodNotAllowedHttpException =>
                    $this->errorResponse('Method not allowed', null, 405),

                default =>
                    $this->errorResponse(
                        config('app.debug') ? $e->getMessage() : 'Server error',
                        null,
                        500
                    ),
            };
        }

        return parent::render($request, $e);
    }
}
