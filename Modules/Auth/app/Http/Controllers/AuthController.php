<?php

namespace Modules\Auth\Http\Controllers;

use Modules\Auth\Services\AuthService;
use Modules\Auth\Models\User;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Services\PasswordlessAuthService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService,
        private PasswordlessAuthService $passwordlessAuth
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return $this->createdResponse([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registration successful.');
    }

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

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return $this->noContentResponse('Logged out successfully.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());
        return $this->noContentResponse('Logged out from all devices.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'defaultAddress');
        return $this->successResponse(new UserResource($user));
    }

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
     * Step 1: Send a 6-digit OTP to the admin's email for passwordless login.
     *
     * POST /api/v1/auth/admin/otp/send
     */
    public function adminOtpSend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Verify the user has admin role
        if (!$user->isAdmin()) {
            return $this->errorResponse('Admin access required.', null, 403);
        }

        // Check user is not banned
        if ($user->status === 'banned') {
            return $this->errorResponse('Your account has been suspended.', null, 403);
        }

        try {
            $this->passwordlessAuth->sendOtp($user, 'admin_passwordless');

            return $this->successResponse(
                ['email' => $user->email],
                'Verification code sent to your email.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send verification code. Please try again.', null, 500);
        }
    }

    /**
     * Step 2: Verify the OTP and log in the admin.
     *
     * POST /api/v1/auth/admin/otp/verify
     */
    public function adminOtpVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Verify the user has admin role
        if (!$user->isAdmin()) {
            return $this->errorResponse('Admin access required.', null, 403);
        }

        // Check user is not banned
        if ($user->status === 'banned') {
            return $this->errorResponse('Your account has been suspended.', null, 403);
        }

        $result = $this->passwordlessAuth->verifyOtp(
            $user,
            $validated['code'],
            'admin_passwordless'
        );

        if (!$result['success']) {
            return $this->errorResponse($result['message'], null, 422);
        }

        return $this->successResponse([
            'user'        => new UserResource($result['user']),
            'token'       => $result['token'],
            'permissions' => $result['permissions'],
        ], 'Login successful.');
    }

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
