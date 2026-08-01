<?php
namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\PasswordlessAuthService;
use Modules\Core\Traits\ApiResponse;

class AdminPassLessAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PasswordlessAuthService $passwordlessAuth
    ) {}

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
        if (! $user->isAdmin()) {
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
        } catch (Exception $e) {
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
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Verify the user has admin role
        if (! $user->isAdmin()) {
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

        if (! $result['success']) {
            return $this->errorResponse($result['message'], null, 422);
        }

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'permissions' => $result['permissions'],
        ], 'Login successful.');
    }
}
