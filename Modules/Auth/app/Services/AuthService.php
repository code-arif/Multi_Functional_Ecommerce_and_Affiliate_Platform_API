<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    // User Registration
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // User Login (password-based)
    public function login(array $credentials, string $deviceName = 'api', ?string $ip = null): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            abort(401, 'Invalid credentials.');
        }

        if ($user->status === 'banned') {
            abort(403, 'Your account has been suspended.');
        }

        $token = $user->createToken($deviceName)->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // Admin Login (password-based)
    public function adminLogin(array $credentials, ?string $ip = null): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            abort(401, 'Invalid credentials.');
        }

        if (!$user->isAdmin() && !$user->isModerator()) {
            abort(403, 'Admin access required.');
        }

        if ($user->status === 'banned') {
            abort(403, 'Your account has been suspended.');
        }

        $token = $user->createToken('admin-api', ['admin'])->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    // Vendor Login (password-based)
    public function vendorLogin(array $credentials, ?string $ip = null): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            abort(401, 'Invalid credentials.');
        }

        if (!$user->isVendor()) {
            abort(403, 'Vendor access required.');
        }

        if ($user->status === 'banned') {
            abort(403, 'Your account has been suspended.');
        }

        $vendor = $user->vendor;

        if (!$vendor || $vendor->status !== 'active') {
            abort(403, 'Your vendor account is not active or pending approval.');
        }

        $token = $user->createToken('vendor-api', ['vendor'])->plainTextToken;

        return ['user' => $user, 'token' => $token, 'vendor' => $vendor];
    }

    // Logout current session
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    // Logout all devices (invalidate all tokens)
    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();
    }

    // Update user profile
    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        return $user->fresh();
    }

    // Update user avatar
    public function updateAvatar(User $user, string $path): User
    {
        $user->update(['avatar' => $path]);
        return $user->fresh();
    }
}
