<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Services\VendorService;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Http\Requests\StoreVendorRequest;
use Modules\Vendor\Http\Requests\UpdateVendorRequest;
use Modules\Vendor\Http\Requests\UploadDocumentRequest;
use Modules\Vendor\Http\Resources\VendorResource;
use Modules\Vendor\Http\Resources\VendorListResource;
use Modules\Vendor\Http\Resources\VendorDocumentResource;
use App\Models\User;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\PasswordlessAuthService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController
{
    use ApiResponse;

    public function __construct(
        private VendorService $vendorService,
        private PasswordlessAuthService $passwordlessAuth
    ) {}

    // Vendor Registration & Profile

    /**
     * POST /api/v1/vendor/register
     * Apply to become a vendor
     */
    public function register(StoreVendorRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->vendor()->exists()) {
            return $this->errorResponse('You are already registered as a vendor.', null, 400);
        }

        $vendor = $this->vendorService->register($request->validated(), $user);

        return $this->createdResponse(
            new VendorResource($vendor->load('profile')),
            'Vendor application submitted. Awaiting approval.'
        );
    }

    /**
     * GET /api/v1/vendor/profile
     * Get authenticated vendor's profile
     */
    public function profile(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor->load(['profile', 'addresses', 'bankAccounts', 'documents']);

        return $this->successResponse(new VendorResource($vendor));
    }

    /**
     * PUT /api/v1/vendor/profile
     * Update vendor shop profile
     */
    public function updateProfile(UpdateVendorRequest $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $data = $request->validated();

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('vendors/logos', 'public');
        }
        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('vendors/banners', 'public');
        }

        $vendor->update($data);

        // Update profile sub-resource if applicable
        $profileData = array_filter([
            'business_type' => $request->business_type ?? null,
            'website'       => $request->website ?? null,
            'return_policy' => $request->return_policy ?? null,
            'shipping_policy' => $request->shipping_policy ?? null,
        ]);

        if (!empty($profileData)) {
            $vendor->profile()->updateOrCreate(
                ['vendor_id' => $vendor->id],
                $profileData
            );
        }

        return $this->successResponse(
            new VendorResource($vendor->fresh()->load('profile')),
            'Profile updated.'
        );
    }

    /**
     * GET /api/v1/vendors/{slug}
     * Public vendor shop page
     */
    public function show(string $slug): JsonResponse
    {
        $vendor = Vendor::active()
            ->with(['profile', 'addresses'])
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->successResponse(new VendorResource($vendor));
    }

    /**
     * GET /api/v1/vendors
     * Public vendor listing
     */
    public function index(Request $request): JsonResponse
    {
        $vendors = Vendor::active()
            ->with('profile')
            ->when($request->search, fn($q, $s) => $q->where('shop_name', 'like', "%{$s}%"))
            ->orderBy('shop_name')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(VendorListResource::collection($vendors));
    }

    /**
     * POST /api/v1/vendor/documents
     * Upload KYC documents
     */
    public function uploadDocument(UploadDocumentRequest $request): JsonResponse
    {
        $vendor = Vendor::where('user_id', $request->user()->id)->firstOrFail();

        $path = $request->file('document')->store("vendors/documents/{$vendor->id}", 'public');

        $document = $vendor->documents()->create([
            'type'            => $request->validated('type'),
            'document_path'   => $path,
            'document_number' => $request->validated('document_number'),
            'expiry_date'     => $request->validated('expiry_date'),
            'status'          => 'pending',
        ]);

        return $this->createdResponse(
            new VendorDocumentResource($document),
            'Document uploaded for verification.'
        );
    }

    /**
     * GET /api/v1/vendor/documents
     * List vendor's uploaded KYC documents
     */
    public function documents(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        $docs = $vendor->documents()->latest()->get();

        return $this->successResponse(
            VendorDocumentResource::collection($docs)
        );
    }

    // ─── Passwordless Login (OTP-based) ──────────────────────────

    // ─── Passwordless Login (OTP-based) ──────────────────────────

    /**
     * Step 1: Send a 6-digit OTP to the vendor's email for passwordless login.
     *
     * POST /api/v1/vendor/auth/otp/send
     */
    public function vendorOtpSend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = $this->resolveVendorUser($validated['email']);
        if (!$user) {
            return $this->errorResponse('Vendor access required.', null, 403);
        }

        try {
            $this->passwordlessAuth->sendOtp($user, 'vendor_passwordless');
            return $this->successResponse(
                ['email' => $user->email],
                'Verification code sent to your email.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to send verification code. Please try again.', null, 500);
        }
    }

    /**
     * Step 2: Verify the OTP and log in the vendor.
     *
     * POST /api/v1/vendor/auth/otp/verify
     */
    public function vendorOtpVerify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|string|size:6',
        ]);

        $user = $this->resolveVendorUser($validated['email']);
        if (!$user) {
            return $this->errorResponse('Vendor access required.', null, 403);
        }

        $result = $this->passwordlessAuth->verifyOtp(
            $user,
            $validated['code'],
            'vendor_passwordless'
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

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Resolve and validate a vendor user for passwordless login.
     *
     * Returns the User if valid (has vendor role + active vendor + not banned),
     * or null if any check fails.
     */
    private function resolveVendorUser(string $email): ?User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !$user->isVendor()) {
            return null;
        }

        $vendor = $user->vendor;
        if (!$vendor || $vendor->status !== 'active') {
            return null;
        }

        if ($user->status === 'banned') {
            return null;
        }

        return $user;
    }
}
