<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthService
{
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

        Log::channel('security')->info('User registered', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return ['user' => $user, 'token' => $token];
    }

    public function login(array $credentials, string $deviceName = 'api'): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            Log::channel('security')->warning('Failed login attempt', [
                'email' => $credentials['email'],
            ]);
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended. Please contact support.'],
            ]);
        }

        // Revoke previous tokens for this device
        $user->tokens()->where('name', $deviceName)->delete();

        $abilities = $user->isAdmin() ? ['admin'] : ['customer'];
        $token     = $user->createToken($deviceName, $abilities)->plainTextToken;

        $user->load('roles');

        Log::channel('security')->info('User logged in', [
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return ['user' => $user, 'token' => $token];
    }

    public function adminLogin(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            Log::channel('security')->warning('Failed admin login attempt', [
                'email' => $credentials['email'],
            ]);
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (!$user->isAdmin() && !$user->isModerator()) {
            Log::channel('security')->warning('Unauthorized admin login attempt', [
                'user_id' => $user->id,
            ]);
            throw ValidationException::withMessages([
                'email' => ['You do not have admin access.'],
            ]);
        }

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
