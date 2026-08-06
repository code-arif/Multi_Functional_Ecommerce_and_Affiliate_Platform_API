<?php

namespace Modules\Vendor\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorDocument;
use Modules\Vendor\Http\Requests\UpdateVendorStatusRequest;
use Modules\Vendor\Transformers\VendorResource;
use Modules\Vendor\Transformers\VendorListResource;
use Modules\Vendor\Transformers\VendorDocumentResource;
use Modules\Vendor\Http\Requests\Admin\StoreVendorRequest;
use Modules\Vendor\Services\VendorManageService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorManageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VendorManageService $vendorManageService
    ) {}

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
     * POST /api/v1/admin/vendors
     * Create a vendor (user account + pending vendor record), then email credentials
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        $vendor = $this->vendorManageService->create($request->validated());

        return $this->createdResponse(
            new VendorResource($vendor->load('user')),
            'Vendor created. A welcome email has been sent to the vendor.'
        );
    }

    /**
     * GET /api/v1/admin/vendors/{uuid}
     * Show vendor details with all relations
     */
    public function show(string $uuid): JsonResponse
    {
        $vendor = Vendor::with(['user', 'profile', 'addresses', 'bankAccounts', 'documents', 'staff.user'])
        ->where('uuid', $uuid)->firstOrFail();
        return $this->successResponse(new VendorResource($vendor));
    }

    /**
     * POST /api/v1/admin/vendors/{uuid}/approve
     * Approve vendor application
     */
    public function approve(string $uuid, Request $request): JsonResponse
    {
        $vendor = Vendor::where('uuid', $uuid)->first();

        if(!$vendor){
            return $this->notFoundResponse('Vendor not found.');
        }

        $vendor = $this->vendorManageService->approve($vendor, $request->user());
        return $this->successResponse(
            new VendorResource($vendor->load('profile')),
            'Vendor approved successfully!'
        );
    }

    /**
     * POST /api/v1/admin/vendors/{uuid}/reject
     * Reject vendor application
     */
    public function reject(string $uuid, UpdateVendorStatusRequest $request): JsonResponse
    {
        $vendor = Vendor::where('uuid', $uuid)->first();

        if(!$vendor){
            return $this->notFoundResponse('Vendor not found.');
        }

        $vendor = $this->vendorManageService->reject($vendor, $request->validated('reason'));
        return $this->successResponse(
            new VendorResource($vendor),
            'Vendor rejected successfully!'
        );
    }

    /**
     * POST /api/v1/admin/vendors/{uuid}/suspend
     * Suspend an active vendor
     */
    public function suspend(string $uuid, UpdateVendorStatusRequest $request): JsonResponse
    {
        $vendor = Vendor::where('uuid', $uuid)->first();

        if(!$vendor){
            return $this->notFoundResponse('Vendor not found.');
        }

        $vendor = $this->vendorManageService->suspend($vendor, $request->validated('reason'));
        return $this->successResponse(
            new VendorResource($vendor),
            'Vendor suspended successfully!'
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
            'status' => 'verified',
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
            'status' => 'rejected',
            'rejection_reason' => $request->validated('reason'),
        ]);

        return $this->successResponse(
            new VendorDocumentResource($document->fresh()),
            'Document rejected.'
        );
    }
}
