<?php

namespace Modules\Vendor\Http\Controllers;

use Modules\Vendor\Services\VendorService;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Models\VendorWalletTransaction;
use Modules\Vendor\Http\Requests\StoreVendorRequest;
use Modules\Vendor\Http\Requests\UpdateVendorRequest;
use Modules\Vendor\Http\Requests\UploadDocumentRequest;
use Modules\Vendor\Http\Requests\PayoutRequest;
use Modules\Vendor\Http\Resources\VendorResource;
use Modules\Vendor\Http\Resources\VendorListResource;
use Modules\Vendor\Http\Resources\VendorDocumentResource;
use Modules\Vendor\Http\Resources\VendorWalletTransactionResource;
use Modules\Vendor\Http\Resources\VendorPayoutResource;
use Modules\Finance\Models\VendorPayoutRequest;
use Modules\Finance\Services\FinanceService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController
{
    use ApiResponse;

    public function __construct(
        private VendorService $vendorService,
        private FinanceService $financeService
    ) {}

    // ─── Vendor Registration & Profile ────────────────────────────

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
        $vendor = Vendor::with(['profile', 'addresses', 'bankAccounts', 'documents'])
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        return $this->successResponse(new VendorResource($vendor));
    }

    /**
     * PUT /api/v1/vendor/profile
     * Update vendor shop profile
     */
    public function updateProfile(UpdateVendorRequest $request): JsonResponse
    {
        $vendor = Vendor::where('user_id', $request->user()->id)->firstOrFail();

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

    // ─── Wallet Management ────────────────────────────────────────

    /**
     * GET /api/v1/vendor/wallet
     * Get authenticated vendor's wallet summary
     */
    public function wallet(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        $summary = $this->financeService->getWalletSummary($vendor);
        return $this->successResponse($summary, 'Wallet summary retrieved.');
    }

    /**
     * GET /api/v1/vendor/wallet/transactions
     * Get wallet transaction history
     */
    public function walletTransactions(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        $transactions = VendorWalletTransaction::where('vendor_id', $vendor->id)
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(
            VendorWalletTransactionResource::collection($transactions)
        );
    }

    /**
     * POST /api/v1/vendor/wallet/payouts
     * Request a payout from wallet balance
     */
    public function requestPayout(PayoutRequest $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        if ($vendor->status !== 'active') {
            return $this->errorResponse('Your vendor account is not active.', null, 403);
        }

        $payout = $this->financeService->requestPayout(
            $vendor,
            $request->validated('amount'),
            $request->validated()
        );

        return $this->createdResponse(
            new VendorPayoutResource($payout),
            'Payout request submitted for approval.'
        );
    }

    /**
     * GET /api/v1/vendor/wallet/payouts
     * List payout requests for the authenticated vendor
     */
    public function payouts(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        $payouts = VendorPayoutRequest::where('vendor_id', $vendor->id)
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(
            VendorPayoutResource::collection($payouts)
        );
    }

    /**
     * GET /api/v1/vendor/wallet/stats
     * Get wallet stats summary (earnings chart data)
     */
    public function walletStats(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not registered as a vendor.', null, 404);
        }

        $stats = [
            'current_balance'  => (float) $vendor->wallet_balance,
            'total_earned'     => (float) $vendor->total_earned,
            'total_withdrawn'  => (float) $vendor->total_withdrawn,
            'pending_payouts'  => (float) VendorPayoutRequest::where('vendor_id', $vendor->id)
                ->where('status', 'pending')->sum('amount'),
            'available'        => max(0, (float) $vendor->wallet_balance),
            'monthly_earnings' => VendorWalletTransaction::where('vendor_id', $vendor->id)
                ->where('type', 'commission')
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        return $this->successResponse($stats, 'Wallet stats retrieved.');
    }
}
