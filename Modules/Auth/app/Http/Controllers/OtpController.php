<?php

namespace Modules\Auth\Http\Controllers;

use Modules\Auth\Services\OtpService;
use App\Models\User;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController
{
    use ApiResponse;

    public function __construct(private OtpService $otpService) {}

    /**
     * POST /api/v1/auth/otp/send
     * Send OTP to user's email or phone
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'    => 'required|string|in:email_verification,password_reset,login',
            'channel' => 'nullable|string|in:email,sms',
            'email'   => 'nullable|email|exists:users,email',
        ]);

        $user = $request->user() ?? User::where('email', $validated['email'] ?? $request->email)->first();

        if (!$user) {
            return $this->successResponse(null, 'If the account exists, an OTP has been sent.');
        }

        try {
            $this->otpService->generate(
                $user,
                $validated['type'],
                $validated['channel'] ?? 'email'
            );

            return $this->successResponse(null, 'OTP sent successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send OTP. Please try again.', null, 500);
        }
    }

    /**
     * POST /api/v1/auth/otp/verify
     * Verify OTP code
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:6',
            'type' => 'required|string|in:email_verification,password_reset,login',
        ]);

        $user = $request->user() ?? User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Invalid request.', null, 400);
        }

        $verified = $this->otpService->verify(
            $user,
            $validated['code'],
            $validated['type']
        );

        if (!$verified) {
            return $this->errorResponse('Invalid or expired OTP code.', null, 422);
        }

        // Handle specific OTP types
        if ($validated['type'] === 'email_verification') {
            $user->update(['email_verified_at' => now()]);
        }

        return $this->successResponse([
            'verified' => true,
            'type'     => $validated['type'],
        ], 'OTP verified successfully.');
    }
}
