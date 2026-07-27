<?php

namespace Modules\Auth\Http\Controllers;

use Modules\Auth\Services\PasswordResetService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordResetController
{
    use ApiResponse;

    public function __construct(private PasswordResetService $passwordResetService) {}

    /**
     * POST /api/v1/auth/password/forgot
     * Send password reset OTP to email
     */
    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
        ]);

        $this->passwordResetService->sendResetLink($validated['email']);

        return $this->successResponse(
            null,
            'If that email is registered, you will receive a password reset OTP.'
        );
    }

    /**
     * POST /api/v1/auth/password/reset
     * Reset password using OTP code
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'                 => 'required|email',
            'code'                  => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $result = $this->passwordResetService->resetWithOtp(
            $validated['email'],
            $validated['code'],
            $validated['password']
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], null, 422);
        }

        return $this->successResponse(null, $result['message']);
    }

    /**
     * POST /api/v1/auth/password/change
     * Change password for authenticated user
     */
    public function change(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        $result = $this->passwordResetService->changePassword(
            $request->user(),
            $validated['current_password'],
            $validated['password']
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], null, 422);
        }

        return $this->successResponse(null, $result['message']);
    }
}
