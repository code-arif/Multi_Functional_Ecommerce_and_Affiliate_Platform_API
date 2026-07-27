<?php

namespace Modules\Inventory\Http\Controllers;

use Modules\Inventory\Services\InventoryService;
use Modules\Catalog\Models\Product;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInventoryController
{
    use ApiResponse;

    public function __construct(private InventoryService $inventoryService) {}

    /**
     * GET /api/v1/admin/inventory/products
     * List all products with stock info (for admin).
     */
    public function products(Request $request): JsonResponse
    {
        $products = Product::with(['category:id,name', 'brand:id,name'])
            ->when($request->status, fn($q, $s) => $q->where('stock_status', $s))
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%"))
            ->orderBy('stock_quantity', 'asc')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($products);
    }

    /**
     * GET /api/v1/admin/inventory/logs
     * All inventory logs (for admin).
     */
    public function logs(Request $request): JsonResponse
    {
        $query = \Modules\Inventory\Models\InventoryLog::with([
            'product:id,name,slug', 'variant:id,name,sku',
            'vendor:id,shop_name', 'createdBy:id,name',
        ]);

        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        $logs = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($logs);
    }

    /**
     * GET /api/v1/admin/inventory/summary
     * Overall inventory summary.
     */
    public function summary(): JsonResponse
    {
        $totalProducts   = Product::count();
        $inStock         = Product::where('stock_status', 'in_stock')->count();
        $outOfStock      = Product::where('stock_status', 'out_of_stock')->count();
        $backOrder       = Product::where('stock_status', 'on_backorder')->count();

        return $this->successResponse([
            'total_products' => $totalProducts,
            'in_stock'       => $inStock,
            'out_of_stock'   => $outOfStock,
            'on_backorder'   => $backOrder,
        ]);
    }
}
