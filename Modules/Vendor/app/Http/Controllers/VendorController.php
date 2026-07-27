<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Services\VendorService;
use Modules\Vendor\Models\Vendor;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController
{
    use ApiResponse;

    public function __construct(private VendorService $vendorService) {}

    /**
     * POST /api/v1/vendor/register
     * Apply to become a vendor
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_name'         => 'required|string|max:200',
            'email'             => 'nullable|email|max:100',
            'phone'             => 'nullable|string|max:20',
            'description'       => 'nullable|string|max:2000',
            'business_type'     => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'website'           => 'nullable|url|max:255',
            'address'           => 'nullable|array',
            'address.address_line_1' => 'required_with:address|string|max:255',
            'address.city'           => 'required_with:address|string|max:100',
            'address.country'        => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        if ($user->isVendor()) {
            return $this->errorResponse('You are already registered as a vendor.', null, 400);
        }

        $vendor = $this->vendorService->register($validated, $user);

        return $this->createdResponse([
            'vendor' => $vendor->load('profile'),
            'status' => 'pending',
        ], 'Vendor application submitted. Awaiting approval.');
    }

    /**
     * GET /api/v1/vendor/profile
     * Get authenticated vendor's profile
     */
    public function profile(Request $request): JsonResponse
    {
        $vendor = Vendor::with(['profile', 'addresses', 'bankAccounts', 'documents'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        return $this->successResponse($vendor);
    }

    /**
     * PUT /api/v1/vendor/profile
     * Update vendor shop profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $vendor = Vendor::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'shop_name'         => 'sometimes|string|max:200',
            'email'             => 'nullable|email|max:100',
            'phone'             => 'nullable|string|max:20',
            'description'       => 'nullable|string|max:2000',
            'logo'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'            => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'business_type'     => 'nullable|string|max:100',
            'website'           => 'nullable|url|max:255',
            'return_policy'     => 'nullable|string|max:50',
            'shipping_policy'   => 'nullable|string|max:50',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('vendors/logos', 'public');
        }
        if ($request->hasFile('banner')) {
            $validated['banner'] = $request->file('banner')->store('vendors/banners', 'public');
        }

        $vendor->update($validated);

        // Update profile if applicable
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
            $vendor->fresh()->load('profile'),
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

        return $this->successResponse($vendor);
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

        return $this->paginatedResponse($vendors);
    }

    /**
     * POST /api/v1/vendor/documents
     * Upload KYC documents
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $vendor = Vendor::where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'type'            => 'required|string|in:trade_license,nid,bin,tin,passport',
            'document'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'document_number' => 'nullable|string|max:100',
            'expiry_date'     => 'nullable|date',
        ]);

        $path = $request->file('document')->store("vendors/documents/{$vendor->id}", 'public');

        $document = $vendor->documents()->create([
            'type'            => $validated['type'],
            'document_path'   => $path,
            'document_number' => $validated['document_number'] ?? null,
            'expiry_date'     => $validated['expiry_date'] ?? null,
            'status'          => 'pending',
        ]);

        return $this->createdResponse($document, 'Document uploaded for verification.');
    }
}
