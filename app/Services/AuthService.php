<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Services\Security\SecurityService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(private SecurityService $security) {}

    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status'   => 'active',
        ]);

        $customerRole = Role::where('name', 'customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole->id);
        }

        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        Log::channel('security')->info('New user registered', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return ['user' => $user->load('roles'), 'token' => $token];
    }

    public function login(array $credentials, string $deviceName = 'api', string $ip = ''): array
    {
        // Check account lockout
        if ($this->security->isAccountLocked($credentials['email'])) {
            $remaining = $this->security->getRemainingLockTime($credentials['email']);
            throw ValidationException::withMessages([
                'email' => ["Account locked due to too many failed attempts. Try again in {$remaining} seconds."],
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->security->recordFailedLogin($credentials['email'], $ip);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact support.'],
            ]);
        }

        // Clear failed attempts on success
        $this->security->recordSuccessfulLogin($credentials['email'], $ip);

        // Revoke old token for same device (single session per device)
        $user->tokens()->where('name', $deviceName)->delete();

        $abilities = $user->isAdmin() ? ['admin'] : ['customer'];
        $token     = $user->createToken($deviceName, $abilities)->plainTextToken;
        $user->load('roles');

        return ['user' => $user, 'token' => $token];
    }

    public function adminLogin(array $credentials, string $ip = ''): array
    {
        if ($this->security->isAccountLocked($credentials['email'])) {
            throw ValidationException::withMessages([
                'email' => ['Account is temporarily locked. Please try again later.'],
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->security->recordFailedLogin($credentials['email'], $ip);
            Log::channel('security')->warning('Failed admin login', [
                'email' => $credentials['email'],
                'ip'    => $ip,
            ]);
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->isAdmin() && !$user->isModerator()) {
            Log::channel('security')->warning('Non-admin attempted admin login', [
                'user_id' => $user->id,
                'ip'      => $ip,
            ]);
            throw ValidationException::withMessages([
                'email' => ['You do not have admin access.'],
            ]);
        }

        $this->security->recordSuccessfulLogin($credentials['email'], $ip);

        $user->tokens()->where('name', 'admin-token')->delete();
        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;
        $user->load('roles.permissions');

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }

    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return $user->fresh();
    }

    public function updateAvatar(User $user, string $path): User
    {
        $user->update(['avatar' => $path]);
        return $user->fresh();
    }
}
