<?php

namespace Modules\Auth\Http\Controllers;

use Modules\Auth\Models\User;
use Modules\Auth\Services\OtpService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController
{
    use ApiResponse;

    public function __construct(private OtpService $otpService) {}

    /**
     * POST /api/v1/auth/email/verify/send
     * Send email verification OTP
     */
    public function sendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified.');
        }

        $this->otpService->generate($user, 'email_verification', 'email');

        return $this->successResponse(null, 'Verification OTP sent to your email.');
    }

    /**
     * POST /api/v1/auth/email/verify
     * Verify email with OTP code
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(null, 'Email already verified.');
        }

        $verified = $this->otpService->verify($user, $validated['code'], 'email_verification');

        if (!$verified) {
            return $this->errorResponse('Invalid or expired verification code.', null, 422);
        }

        $user->markEmailAsVerified();

        return $this->successResponse(null, 'Email verified successfully.');
    }

    /**
     * GET /api/v1/auth/email/status
     * Check email verification status
     */
    public function status(Request $request): JsonResponse
    {
        return $this->successResponse([
            'email'          => $request->user()->email,
            'is_verified'    => $request->user()->hasVerifiedEmail(),
            'verified_at'    => $request->user()->email_verified_at,
        ]);
    }
}
