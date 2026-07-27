<?php

namespace Modules\Finance\Http\Controllers;

use Modules\Finance\Services\FinanceService;
use Modules\Finance\Http\Resources\PayoutResource;
use Modules\Finance\Http\Resources\CommissionResource;
use Modules\Finance\Http\Resources\SettlementResource;
use Modules\Finance\Http\Resources\WalletTransactionResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorFinanceController
{
    use ApiResponse;

    public function __construct(private FinanceService $financeService) {}

    /**
     * GET /api/v1/vendor/finance/wallet
     * Get wallet summary.
     */
    public function wallet(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) return $this->errorResponse('Not a vendor.', null, 403);

        $summary = $this->financeService->getWalletSummary($vendor);
        return $this->successResponse($summary);
    }

    /**
     * GET /api/v1/vendor/finance/transactions
     * Get wallet transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) return $this->errorResponse('Not a vendor.', null, 403);

        $txns = $this->financeService->getWalletTransactions($vendor->id, $request->only(['type', 'per_page']));
        return $this->paginatedResponse(WalletTransactionResource::collection($txns));
    }

    /**
     * GET /api/v1/vendor/finance/commissions
     * Get vendor commissions.
     */
    public function commissions(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) return $this->errorResponse('Not a vendor.', null, 403);

        $commissions = $this->financeService->getVendorCommissions($vendor->id, $request->only(['status', 'per_page']));
        return $this->paginatedResponse(CommissionResource::collection($commissions));
    }

    /**
     * POST /api/v1/vendor/finance/payouts
     * Request a payout.
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) return $this->errorResponse('Not a vendor.', null, 403);

        $validated = $request->validate([
            'amount'          => 'required|numeric|min:100',
            'payment_method'  => 'nullable|string|max:50',
            'payment_details' => 'nullable|string|max:500',
            'notes'           => 'nullable|string|max:500',
        ]);

        $payout = $this->financeService->requestPayout($vendor, $validated['amount'], $validated);

        return $this->createdResponse(new PayoutResource($payout), 'Payout request submitted.');
    }

    /**
     * GET /api/v1/vendor/finance/payouts
     * List vendor payouts.
     */
    public function payouts(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) return $this->errorResponse('Not a vendor.', null, 403);

        $payouts = $this->financeService->getVendorPayouts($vendor->id, $request->only(['status', 'per_page']));
        return $this->paginatedResponse(PayoutResource::collection($payouts));
    }

    /**
     * GET /api/v1/vendor/finance/settlements
     * List settlements.
     */
    public function settlements(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) return $this->errorResponse('Not a vendor.', null, 403);

        $settlements = $this->financeService->getVendorSettlements($vendor->id);
        return $this->paginatedResponse(SettlementResource::collection($settlements));
    }
}
