<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\AuthService;
use Modules\Core\Traits\ApiResponse;
use Modules\Vendor\Transformers\VendorResource;

class AuthController
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService,
    ) {}

    // User Registration
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return $this->createdResponse([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registration successful.');
    }

    // User Login
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->only('email', 'password'),
            $request->device_name ?? 'api',
            $request->ip()
        );
        return $this->successResponse([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful.');
    }

    // Admin Login (password-based)
    public function adminLogin(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->adminLogin(
            $request->only('email', 'password'),
            $request->ip()
        );
        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'permissions' => $result['user']->roles
                ->flatMap(fn($r) => $r->permissions)
                ->pluck('name')
                ->unique()
                ->values(),
        ], 'Admin login successful.');
    }

    // Password-based vendor login — validates vendor role & active status.
    public function vendorLogin(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->vendorLogin(
            $request->only('email', 'password'),
            $request->ip()
        );

        return $this->successResponse([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
            'shop'  => new VendorResource($result['vendor']->load(['profile', 'addresses'])),
        ], 'Vendor login successful.');
    }

    // Logout the current user (invalidate the current token)
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return $this->noContentResponse('Logged out successfully.');
    }

    // Logout from all devices (invalidate all tokens for the user)
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());
        return $this->noContentResponse('Logged out from all devices.');
    }

    // Get the authenticated user's profile
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'defaultAddress');
        return $this->successResponse(new UserResource($user));
    }

    // Update the authenticated user's profile
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'phone' => "sometimes|string|max:20|unique:users,phone,{$request->user()->id}",
            'current_password' => 'required_with:password|string',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($request->has('password')) {
            if (!password_verify($request->current_password, $request->user()->password)) {
                return $this->errorResponse('Current password is incorrect.', null, 422);
            }
        }

        $user = $this->authService->updateProfile(
            $request->user(),
            $request->only(['name', 'phone', 'password'])
        );

        return $this->successResponse(new UserResource($user), 'Profile updated.');
    }

    // Update the authenticated user's avatar
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');
        $user = $this->authService->updateAvatar($request->user(), $path);

        return $this->successResponse(['avatar_url' => $user->avatar_url], 'Avatar updated.');
    }
}
