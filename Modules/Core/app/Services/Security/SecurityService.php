<?php

namespace Modules\Core\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityService
{
    public function unlockAccount(string $email): void
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->update(['status' => 'active']);
            Log::info('Account unlocked', ['email' => $email]);
        }
    }

    public function blockIp(string $ip, int $duration = 3600, string $reason = ''): void
    {
        Cache::put("blocked_ip:{$ip}", [
            'reason'   => $reason,
            'blocked_at' => now(),
        ], $duration);

        Log::warning('IP blocked', [
            'ip'       => $ip,
            'duration' => $duration,
            'reason'   => $reason,
        ]);
    }

    public function isIpBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }

    public function revokeAllTokens(int $userId): void
    {
        $user = User::find($userId);
        if ($user) {
            $user->tokens()->delete();
            Log::info('All tokens revoked', ['user_id' => $userId]);
        }
    }
}
