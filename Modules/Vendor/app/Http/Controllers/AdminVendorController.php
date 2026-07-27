<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorDocument;
use Modules\Vendor\Services\VendorService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVendorController
{
    use ApiResponse;

    public function __construct(private VendorService $vendorService) {}

    /**
     * GET /api/v1/admin/vendors
     * List all vendors for admin
     */
    public function index(Request $request): JsonResponse
    {
        $vendors = Vendor::with(['user', 'profile'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where('shop_name', 'like', "%{$s}%"))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($vendors);
    }

    /**
     * GET /api/v1/admin/vendors/{vendor}
     * Show vendor details with all relations
     */
    public function show(Vendor $vendor): JsonResponse
    {
        $vendor->load(['user', 'profile', 'addresses', 'bankAccounts', 'documents', 'staff.user']);
        return $this->successResponse($vendor);
    }

    /**
     * POST /api/v1/admin/vendors/{vendor}/approve
     * Approve vendor application
     */
    public function approve(Vendor $vendor, Request $request): JsonResponse
    {
        $vendor = $this->vendorService->approve($vendor, $request->user());
        return $this->successResponse($vendor, 'Vendor approved.');
    }

    /**
     * POST /api/v1/admin/vendors/{vendor}/reject
     * Reject vendor application
     */
    public function reject(Vendor $vendor, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $vendor = $this->vendorService->reject($vendor, $validated['reason']);
        return $this->successResponse($vendor, 'Vendor rejected.');
    }

    /**
     * POST /api/v1/admin/vendors/{vendor}/suspend
     * Suspend an active vendor
     */
    public function suspend(Vendor $vendor, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $vendor = $this->vendorService->suspend($vendor, $validated['reason'] ?? null);
        return $this->successResponse($vendor, 'Vendor suspended.');
    }

    /**
     * GET /api/v1/admin/vendors/pending
     * Quick access to pending vendors
     */
    public function pending(): JsonResponse
    {
        $vendors = Vendor::with('user')->pending()->latest()->get();
        return $this->successResponse($vendors);
    }

    /**
     * POST /api/v1/admin/vendors/documents/{document}/verify
     * Verify a vendor document
     */
    public function verifyDocument(VendorDocument $document): JsonResponse
    {
        $document->update([
            'status'      => 'verified',
            'verified_at' => now(),
            'verified_by' => request()->user()->id,
        ]);

        return $this->successResponse($document->fresh(), 'Document verified.');
    }

    /**
     * POST /api/v1/admin/vendors/documents/{document}/reject
     * Reject a vendor document
     */
    public function rejectDocument(VendorDocument $document, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $document->update([
            'status'           => 'rejected',
            'rejection_reason' => $validated['reason'],
        ]);

        return $this->successResponse($document->fresh(), 'Document rejected.');
    }
}
