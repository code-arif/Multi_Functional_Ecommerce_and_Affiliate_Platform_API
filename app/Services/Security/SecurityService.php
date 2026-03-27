<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * SecurityService
 *
 * Handles:
 *  - Login brute-force protection
 *  - IP-based lockout
 *  - Suspicious activity detection
 *  - Token management
 */
class SecurityService
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900;  // 15 minutes in seconds
    private const ATTEMPT_WINDOW = 300;  // Track attempts over 5 minutes
    private const IP_BLOCK_THRESHOLD = 20;   // Requests from same IP in 1 min

    // ─── Login Brute Force ────────────────────────────────────────

    public function recordFailedLogin(string $email, string $ip): void
    {
        $emailKey = "login_attempts:email:{$email}";
        $ipKey    = "login_attempts:ip:{$ip}";

        $emailAttempts = Cache::get($emailKey, 0) + 1;
        Cache::put($emailKey, $emailAttempts, self::ATTEMPT_WINDOW);

        $ipAttempts = Cache::get($ipKey, 0) + 1;
        Cache::put($ipKey, $ipAttempts, self::ATTEMPT_WINDOW);

        if ($emailAttempts >= self::MAX_LOGIN_ATTEMPTS) {
            $this->lockAccount($email);
            Log::channel('security')->warning('Account locked after failed attempts', [
                'email'    => $email,
                'ip'       => $ip,
                'attempts' => $emailAttempts,
            ]);
        }
    }

    public function recordSuccessfulLogin(string $email, string $ip): void
    {
        // Clear failed attempt counters on success
        Cache::forget("login_attempts:email:{$email}");
        Cache::forget("locked_account:{$email}");

        Log::channel('security')->info('Successful login', [
            'email' => $email,
            'ip'    => $ip,
        ]);
    }

    public function isAccountLocked(string $email): bool
    {
        return Cache::has("locked_account:{$email}");
    }

    public function getRemainingLockTime(string $email): int
    {
        return (int) Cache::get("locked_account:{$email}_ttl", 0);
    }

    public function lockAccount(string $email): void
    {
        Cache::put("locked_account:{$email}", true, self::LOCKOUT_DURATION);
        Cache::put("locked_account:{$email}_ttl", self::LOCKOUT_DURATION, self::LOCKOUT_DURATION);
    }

    public function unlockAccount(string $email): void
    {
        Cache::forget("locked_account:{$email}");
        Cache::forget("login_attempts:email:{$email}");
        Log::channel('security')->info('Account manually unlocked', ['email' => $email]);
    }

    // ─── IP Blocking ──────────────────────────────────────────────

    public function isIpBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }

    public function blockIp(string $ip, int $duration = 3600, string $reason = ''): void
    {
        Cache::put("blocked_ip:{$ip}", ['reason' => $reason, 'blocked_at' => now()], $duration);
        Log::channel('security')->warning('IP blocked', [
            'ip'       => $ip,
            'duration' => $duration,
            'reason'   => $reason,
        ]);
    }

    public function unblockIp(string $ip): void
    {
        Cache::forget("blocked_ip:{$ip}");
        Log::channel('security')->info('IP unblocked', ['ip' => $ip]);
    }

    // ─── Token Security ───────────────────────────────────────────

    /**
     * Revoke all tokens for a user (force logout all devices).
     */
    public function revokeAllTokens(int $userId): void
    {
        User::find($userId)?->tokens()->delete();

        Log::channel('security')->info('All tokens revoked', ['user_id' => $userId]);
    }

    /**
     * Revoke tokens older than N days.
     */
    public function pruneExpiredTokens(int $days = 30): int
    {
        $count = PersonalAccessToken::where(
            'last_used_at',
            '<',
            now()->subDays($days)
        )->orWhereNull('last_used_at')->where(
            'created_at',
            '<',
            now()->subDays($days)
        )->delete();

        Log::channel('security')->info("Pruned {$count} expired tokens");

        return $count;
    }

    // ─── Suspicious Activity ──────────────────────────────────────

    /**
     * Detect abnormally high request rate from an IP.
     */
    public function detectSuspiciousActivity(string $ip): bool
    {
        $key    = "request_count:ip:{$ip}";
        $count  = Cache::increment($key);

        if ($count === 1) {
            Cache::expire($key, 60); // 1-minute window
        }

        if ($count > self::IP_BLOCK_THRESHOLD) {
            Log::channel('security')->warning('Suspicious activity detected', [
                'ip'    => $ip,
                'count' => $count,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Generate a cryptographically secure random token.
     */
    public function generateSecureToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}
