<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\API\V1\BaseController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(private AuthService $authService) {}

    /**
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->createdResponse([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registration successful.');
    }

    /**
     * POST /api/v1/auth/login
     */
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

    /**
     * POST /api/v1/auth/admin/login
     */
    public function adminLogin(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->adminLogin(
            $request->only('email', 'password'),
            $request->ip()
        );

        return $this->successResponse([
            'user'        => new UserResource($result['user']),
            'token'       => $result['token'],
            'permissions' => $result['user']->roles
                ->flatMap(fn($r) => $r->permissions)
                ->pluck('name')
                ->unique()
                ->values(),
        ], 'Admin login successful.');
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return $this->noContentResponse('Logged out successfully.');
    }

    /**
     * POST /api/v1/auth/logout-all — Revoke all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());
        return $this->noContentResponse('Logged out from all devices.');
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'defaultAddress');
        return $this->successResponse(new UserResource($user));
    }

    /**
     * PUT /api/v1/auth/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'             => 'sometimes|string|max:100',
            'phone'            => "sometimes|string|max:20|unique:users,phone,{$request->user()->id}",
            'current_password' => 'required_with:password|string',
            'password'         => 'sometimes|string|min:8|confirmed',
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

    /**
     * POST /api/v1/auth/avatar
     */
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
