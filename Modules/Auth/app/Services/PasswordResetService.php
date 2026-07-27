<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function __construct(private OtpService $otpService) {}

    /**
     * Send password reset link via email
     */
    public function sendResetLink(string $email): string
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return Password::RESET_LINK_SENT; // Don't reveal if user exists
        }

        // Generate OTP for password reset
        $this->otpService->generate($user, 'password_reset', 'email');

        return Password::RESET_LINK_SENT;
    }

    /**
     * Reset password using OTP code
     */
    public function resetWithOtp(string $email, string $code, string $newPassword): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid request.'];
        }

        $verified = $this->otpService->verify($user, $code, 'password_reset');

        if (!$verified) {
            return ['success' => false, 'message' => 'Invalid or expired OTP code.'];
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all existing tokens to force re-login
        $user->tokens()->delete();

        return ['success' => true, 'message' => 'Password reset successful. Please login with your new password.'];
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        return ['success' => true, 'message' => 'Password changed successfully.'];
    }
}
