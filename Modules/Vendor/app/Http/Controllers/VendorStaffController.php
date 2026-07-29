<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Models\VendorStaff;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Services\VendorService;
use Modules\Vendor\Http\Resources\VendorStaffResource;
use Modules\Auth\Models\User;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorStaffController
{
    use ApiResponse;

    public function __construct(
        private VendorService $vendorService
    ) {}

    /**
     * GET /api/v1/vendor/staff
     * List all staff members for the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $staff = VendorStaff::with('user')
            ->where('vendor_id', $vendor->id)
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->get();

        return $this->successResponse(
            VendorStaffResource::collection($staff)
        );
    }

    /**
     * POST /api/v1/vendor/staff
     * Invite a new staff member or add existing user as staff.
     */
    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email',
            'role'     => 'required|string|in:staff,manager,order_processor,product_manager',
            'password' => 'nullable|string|min:8',
        ]);

        // Find or create the user
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $tempPassword = $validated['password'] ?? \Illuminate\Support\Str::random(12);
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => bcrypt($tempPassword),
                'status'   => 'active',
            ]);
        }

        // Check if already staff for this vendor
        $exists = VendorStaff::where('vendor_id', $vendor->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return $this->errorResponse('This user is already a staff member.', null, 400);
        }

        // Assign vendor role to user
        if (!$user->isVendor()) {
            $user->assignRole('vendor');
        }

        $staff = $this->vendorService->addStaff($vendor, $user, $validated['role']);

        return $this->createdResponse(
            new VendorStaffResource($staff->load('user')),
            'Staff member added successfully.'
        );
    }

    /**
     * PUT /api/v1/vendor/staff/{staff}
     * Update staff member role or permissions.
     */
    public function update(Request $request, VendorStaff $staff): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($staff->vendor_id !== $vendor->id) {
            return $this->errorResponse('Staff member not found.', null, 404);
        }

        $validated = $request->validate([
            'role'        => 'sometimes|string|in:staff,manager,order_processor,product_manager',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string',
        ]);

        $staff->update($validated);

        return $this->successResponse(
            new VendorStaffResource($staff->fresh()->load('user')),
            'Staff member updated.'
        );
    }

    /**
     * POST /api/v1/vendor/staff/{staff}/toggle-status
     * Toggle staff member active/inactive status.
     */
    public function toggleStatus(Request $request, VendorStaff $staff): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($staff->vendor_id !== $vendor->id) {
            return $this->errorResponse('Staff member not found.', null, 404);
        }

        $staff->update([
            'is_active' => !$staff->is_active,
        ]);

        return $this->successResponse(
            new VendorStaffResource($staff->fresh()->load('user')),
            $staff->is_active ? 'Staff member activated.' : 'Staff member deactivated.'
        );
    }

    /**
     * DELETE /api/v1/vendor/staff/{staff}
     * Remove a staff member.
     */
    public function destroy(Request $request, VendorStaff $staff): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if ($staff->vendor_id !== $vendor->id) {
            return $this->errorResponse('Staff member not found.', null, 404);
        }

        $staff->delete();

        return $this->noContentResponse('Staff member removed.');
    }
}
