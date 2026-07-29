<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorProfile;
use Modules\Vendor\Http\Resources\VendorResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorSettingsController
{
    use ApiResponse;

    /**
     * GET /api/v1/vendor/settings
     * Get the authenticated vendor's settings.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor->load(['profile', 'addresses', 'bankAccounts']);

        return $this->successResponse([
            'shop' => new VendorResource($vendor),
            'settings' => [
                'commission_rate' => (float) $vendor->commission_rate,
                'commission_type' => $vendor->commission_type,
                'auto_approve'    => config('ecommerce.vendor.auto_approve', false),
                'max_staff'       => config('ecommerce.vendor.max_staff_per_vendor', 10),
                'allowed_document_types' => config('ecommerce.vendor.allowed_document_types', []),
            ],
        ]);
    }

    /**
     * POST /api/v1/vendor/settings
     * Update vendor-specific settings.
     */
    public function update(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $validated = $request->validate([
            'shop_name'      => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:20',
            'description'    => 'nullable|string|max:5000',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'business_type'  => 'nullable|string|max:100',
            'website'        => 'nullable|url|max:255',
            'return_policy'  => 'nullable|string|max:10000',
            'shipping_policy' => 'nullable|string|max:10000',
        ]);

        $shopData = array_filter([
            'shop_name'   => $validated['shop_name'] ?? null,
            'email'       => $validated['email'] ?? null,
            'phone'       => $validated['phone'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($shopData)) {
            $vendor->update($shopData);
        }

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('vendors/logos', 'public');
            $vendor->update(['logo' => $path]);
        }
        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('vendors/banners', 'public');
            $vendor->update(['banner' => $path]);
        }

        // Update profile sub-resource
        $profileData = array_filter([
            'business_type'  => $validated['business_type'] ?? null,
            'website'        => $validated['website'] ?? null,
            'return_policy'  => $validated['return_policy'] ?? null,
            'shipping_policy' => $validated['shipping_policy'] ?? null,
        ]);

        if (!empty($profileData)) {
            $vendor->profile()->updateOrCreate(
                ['vendor_id' => $vendor->id],
                $profileData
            );
        }

        return $this->successResponse(
            new VendorResource($vendor->fresh()->load(['profile', 'addresses', 'bankAccounts'])),
            'Settings updated.'
        );
    }
}
