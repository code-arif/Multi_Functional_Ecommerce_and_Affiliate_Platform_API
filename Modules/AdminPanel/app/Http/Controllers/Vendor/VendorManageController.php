<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorDocument;
use Modules\Vendor\Services\VendorService;
use Modules\Vendor\Http\Requests\UpdateVendorStatusRequest;
use Modules\Vendor\Http\Resources\VendorResource;
use Modules\Vendor\Http\Resources\VendorListResource;
use Modules\Vendor\Http\Resources\VendorDocumentResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorManageController
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

        return $this->paginatedResponse(VendorResource::collection($vendors));
    }

    /**
     * GET /api/v1/admin/vendors/{vendor}
     * Show vendor details with all relations
     */
    public function show(Vendor $vendor): JsonResponse
    {
        $vendor->load(['user', 'profile', 'addresses', 'bankAccounts', 'documents', 'staff.user']);
        return $this->successResponse(new VendorResource($vendor));
    }

    /**
     * POST /api/v1/admin/vendors/{vendor}/approve
     * Approve vendor application
     */
    public function approve(Vendor $vendor, Request $request): JsonResponse
    {
        $vendor = $this->vendorService->approve($vendor, $request->user());
        return $this->successResponse(
            new VendorResource($vendor->load('profile')),
            'Vendor approved.'
        );
    }

    /**
     * POST /api/v1/admin/vendors/{vendor}/reject
     * Reject vendor application
     */
    public function reject(Vendor $vendor, UpdateVendorStatusRequest $request): JsonResponse
    {
        $vendor = $this->vendorService->reject($vendor, $request->validated('reason'));
        return $this->successResponse(
            new VendorResource($vendor),
            'Vendor rejected.'
        );
    }

    /**
     * POST /api/v1/admin/vendors/{vendor}/suspend
     * Suspend an active vendor
     */
    public function suspend(Vendor $vendor, UpdateVendorStatusRequest $request): JsonResponse
    {
        $vendor = $this->vendorService->suspend($vendor, $request->validated('reason'));
        return $this->successResponse(
            new VendorResource($vendor),
            'Vendor suspended.'
        );
    }

    /**
     * GET /api/v1/admin/vendors/pending
     * Quick access to pending vendors
     */
    public function pending(): JsonResponse
    {
        $vendors = Vendor::with('user')->pending()->latest()->get();
        return $this->successResponse(VendorResource::collection($vendors));
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

        return $this->successResponse(
            new VendorDocumentResource($document->fresh()),
            'Document verified.'
        );
    }

    /**
     * POST /api/v1/admin/vendors/documents/{document}/reject
     * Reject a vendor document
     */
    public function rejectDocument(VendorDocument $document, UpdateVendorStatusRequest $request): JsonResponse
    {
        $document->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->validated('reason'),
        ]);

        return $this->successResponse(
            new VendorDocumentResource($document->fresh()),
            'Document rejected.'
        );
    }
}
