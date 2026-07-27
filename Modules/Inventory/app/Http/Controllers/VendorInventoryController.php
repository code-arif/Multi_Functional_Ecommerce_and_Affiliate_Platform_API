<?php

namespace Modules\Inventory\Http\Controllers;

use Modules\Inventory\Services\InventoryService;
use Modules\Catalog\Models\VendorProductPrice;
use Modules\Vendor\Models\Vendor;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorInventoryController
{
    use ApiResponse;

    public function __construct(private InventoryService $inventoryService) {}

    /**
     * GET /api/v1/vendor/inventory
     * List vendor's inventory (vendor-product prices with stock info).
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $items = VendorProductPrice::where('vendor_id', $vendor->id)
            ->with('product:id,name,slug,sku,thumbnail,status')
            ->when($request->stock_status, fn($q, $s) => $q->where('stock_status', $s))
            ->when($request->search, fn($q, $s) =>
                $q->whereHas('product', fn($q) => $q->where('name', 'like', "%{$s}%")))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($items);
    }

    /**
     * GET /api/v1/vendor/inventory/summary
     * Stock summary counts for dashboard.
     */
    public function summary(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $summary = $this->inventoryService->getStockSummary($vendor->id);

        return $this->successResponse($summary);
    }

    /**
     * GET /api/v1/vendor/inventory/low-stock
     * Low stock products.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $products = $this->inventoryService->getLowStockProducts($vendor->id);

        return $this->successResponse($products);
    }

    /**
     * GET /api/v1/vendor/inventory/out-of-stock
     * Out of stock products.
     */
    public function outOfStock(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $products = $this->inventoryService->getOutOfStockProducts($vendor->id);

        return $this->successResponse($products);
    }

    /**
     * POST /api/v1/vendor/inventory/adjust
     * Adjust stock for a vendor product.
     */
    public function adjust(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $validated = $request->validate([
            'vendor_product_id' => 'required|exists:vendor_product_prices,id',
            'quantity'          => 'required|integer',
            'notes'             => 'nullable|string|max:500',
        ]);

        $vendorProduct = VendorProductPrice::where('id', $validated['vendor_product_id'])
            ->where('vendor_id', $vendor->id)
            ->firstOrFail();

        $this->inventoryService->adjustVendorProductStock(
            $vendorProduct,
            $validated['quantity'],
            'adjustment',
            $validated['notes'] ?? null,
            null,
            $request->user()
        );

        return $this->successResponse(
            $vendorProduct->fresh(),
            'Stock adjusted successfully.'
        );
    }

    /**
     * GET /api/v1/vendor/inventory/logs
     * Stock adjustment history for this vendor.
     */
    public function logs(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->errorResponse('You are not a vendor.', null, 403);
        }

        $logs = \Modules\Inventory\Models\InventoryLog::with(['product', 'createdBy', 'warehouse'])
            ->where('vendor_id', $vendor->id)
            ->when($request->product_id, fn($q, $id) => $q->where('product_id', $id))
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($logs);
    }
}
