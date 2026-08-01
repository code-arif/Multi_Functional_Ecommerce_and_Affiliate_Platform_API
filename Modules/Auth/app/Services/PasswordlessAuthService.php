<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\OtpCode;

/**
 * PasswordlessAuthService — reusable OTP-based authentication.
 *
 * Supports passwordless login for any actor type (admin, vendor, customer).
 * The flow:
 *   1. sendOtp(identifier, type)  → generates 6-digit OTP, sends to email
 *   2. verifyOtp(identifier, code, type) → verifies OTP, returns fresh Sanctum token + user
 *
 * Extend by calling with different $type values:
 *   - 'admin_passwordless'   → admin login
 *   - 'vendor_passwordless'  → vendor login (future)
 *   - 'customer_passwordless' → customer login (future)
 */
class PasswordlessAuthService
{
    /**
     * Step 1: Send a 6-digit OTP to the user's email.
     *
     * @param  User   $user  The user to send the OTP to
     * @param  string $type  OTP type (e.g. 'admin_passwordless', 'vendor_passwordless')
     * @return OtpCode
     */
    public function sendOtp(User $user, string $type): OtpCode
    {
        // Invalidate any existing valid OTPs of same type for this user
        OtpCode::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        // Generate a secure random 6-digit code
        $code = (string) random_int(100000, 999999);

        $otp = OtpCode::create([
            'user_id'     => $user->id,
            'code'        => $code,
            'type'        => $type,
            'channel'     => 'email',
            'destination' => $user->email,
            'expires_at'  => now()->addMinutes(10),
        ]);

        $this->sendEmail($user, $otp);

        Log::info("Passwordless OTP sent to {$user->email}: {$otp->code} (type: {$type})");

        return $otp;
    }

    /**
     * Step 2: Verify the OTP and create an authenticated session.
     *
     * @param  User   $user  The user attempting to log in
     * @param  string $code  The 6-digit OTP code
     * @param  string $type  OTP type (must match the type used in sendOtp)
     * @return array{success: bool, message: string, user?: User, token?: string, permissions?: array}
     */
    public function verifyOtp(User $user, string $code, string $type): array
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP code.',
            ];
        }

        // Mark OTP as used
        $otp->update(['used_at' => now()]);

        // Revoke all existing tokens (force re-login from this device)
        $user->tokens()->delete();

        // Create a new token with appropriate abilities
        $token = $user->createToken("passwordless-{$type}", [$type])->plainTextToken;

        // Refresh user and eager-load roles for permission resolution
        $user = $user->fresh()->load('roles');

        // Resolve permissions from Spatie roles
        $permissions = $user->roles
            ->flatMap(fn($r) => $r->permissions)
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();

        return [
            'success'     => true,
            'message'     => 'Login successful.',
            'user'        => $user,
            'token'       => $token,
            'permissions' => $permissions,
        ];
    }

    /**
     * Send the OTP code via email.
     */
    private function sendEmail(User $user, OtpCode $otp): void
    {
        try {
            Mail::raw(
                $this->buildEmailBody($user, $otp),
                function ($message) use ($user, $otp) {
                    $message->to($user->email)
                        ->subject($this->buildEmailSubject($otp));
                }
            );
        } catch (\Exception $e) {
            Log::error('Failed to send passwordless OTP email', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'type'    => $otp->type,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build a user-friendly email subject based on OTP type.
     */
    private function buildEmailSubject(OtpCode $otp): string
    {
        return match ($otp->type) {
            'admin_passwordless'  => 'Your Admin Login Verification Code',
            'vendor_passwordless' => 'Your Vendor Login Verification Code',
            default               => 'Your Login Verification Code',
        };
    }

    /**
     * Build the email body with the OTP code.
     */
    private function buildEmailBody(User $user, OtpCode $otp): string
    {
        $greeting = match ($otp->type) {
            'admin_passwordless'  => 'Hello Admin,',
            'vendor_passwordless' => 'Hello Vendor,',
            default               => 'Hello ' . ($user->name ?? 'User') . ',',
        };

        return implode("\n\n", [
            $greeting,
            "Your verification code is: {$otp->code}",
            "This code expires in 10 minutes.",
            "If you did not request this code, please ignore this email.",
            "---",
            "This is an automated message. Please do not reply.",
        ]);
    }
}
