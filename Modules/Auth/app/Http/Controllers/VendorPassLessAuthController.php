<?php
namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Services\PasswordlessAuthService;
use Modules\Auth\Transformers\UserResource;
use Modules\Vendor\Transformers\VendorResource;
use Modules\Core\Traits\ApiResponse;

class VendorPassLessAuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PasswordlessAuthService $passwordlessAuth
    ) {}

    /**
     * Step 1: Send a 6-digit OTP to the vendor's email for passwordless login.
     *
     * POST /api/v1/auth/vendor/otp/send
     */
    public function vendorOtpSend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Verify the user has vendor role
        if (! $user->isVendor()) {
            return $this->errorResponse('Vendor access required.', null, 403);
        }

        // Check user is not banned
        if ($user->status === 'banned') {
            return $this->errorResponse('Your account has been suspended.', null, 403);
        }

        // Check if vendor profile exists and is active
        $vendor = $user->vendor;
        if (! $vendor || $vendor->status !== 'active') {
            return $this->errorResponse('Your vendor account is not active or pending approval.', null, 403);
        }

        try {
            $code = $this->passwordlessAuth->sendOtp($user, 'vendor_passwordless');

            return $this->successResponse(
                ['email' => $user->email, 'code' => $code->code],
                'Verification code sent to your email.'
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to send verification code. Please try again.', null, 500);
        }
    }

    /**
     * Step 2: Verify the OTP and log in the vendor.
     *
     * POST /api/v1/auth/vendor/otp/verify
     */
    public function vendorOtpVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Verify the user has vendor role
        if (! $user->isVendor()) {
            return $this->errorResponse('Vendor access required.', null, 403);
        }

        // Check user is not banned
        if ($user->status === 'banned') {
            return $this->errorResponse('Your account has been suspended.', null, 403);
        }

        // Check if vendor profile exists and is active
        $vendor = $user->vendor;
        if (! $vendor || $vendor->status !== 'active') {
            return $this->errorResponse('Your vendor account is not active or pending approval.', null, 403);
        }

        $result = $this->passwordlessAuth->verifyOtp(
            $user,
            $validated['code'],
            'vendor_passwordless'
        );

        if (! $result['success']) {
            return $this->errorResponse($result['message'], null, 422);
        }

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'permissions' => $result['permissions'],
            'shop' => new VendorResource($vendor->load(['profile', 'addresses'])),
        ], 'Login successful.');
    }
}
