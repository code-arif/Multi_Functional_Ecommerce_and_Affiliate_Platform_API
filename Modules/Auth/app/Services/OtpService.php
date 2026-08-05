<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    public function generate(User $user, string $type, string $channel = 'email', ?string $destination = null): OtpCode
    {
        // Invalidate any existing valid OTPs of same type
        OtpCode::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);
        $destination = $destination ?? $user->email;

        $otp = OtpCode::create([
            'user_id'     => $user->id,
            'code'        => $code,
            'type'        => $type,
            'channel'     => $channel,
            'destination' => $destination,
            'expires_at'  => now()->addMinutes(10),
        ]);

        $this->send($otp, $user);

        return $otp;
    }

    public function verify(User $user, string $code, string $type): bool
    {
        $otp = OtpCode::where('user_id', $user->id)
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }

    public function verifyAndGet(string $destination, string $code, string $type): ?OtpCode
    {
        $otp = OtpCode::where('destination', $destination)
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            return null;
        }

        $otp->update(['used_at' => now()]);

        return $otp;
    }

    private function send(OtpCode $otp, User $user): void
    {
        if ($otp->channel === 'email') {
            try {
                Mail::raw(
                    "Your {$otp->type} code is: {$otp->code}\nThis code expires in 10 minutes.",
                    function ($message) use ($user, $otp) {
                        $message->to($user->email)
                            ->subject("Your {$otp->type} code");
                    }
                );
                \Illuminate\Support\Facades\Log::info("OTP sent to {$user->email}: {$otp->code} (type: {$otp->type})");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send OTP email', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'type'    => $otp->type,
                    'error'   => $e->getMessage(),
                ]);
            }
        } else {
            \Illuminate\Support\Facades\Log::info("SMS OTP for {$user->phone}: {$otp->code} (type: {$otp->type})");
        }
    }
}
