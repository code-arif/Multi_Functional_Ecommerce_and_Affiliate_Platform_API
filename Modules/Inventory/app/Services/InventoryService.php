<?php

namespace Modules\Inventory\Services;

use \Illuminate\Support\Facades\Log;
use \Modules\Notifications\Notifications\LowStockNotification;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Inventory\Models\InventoryLog;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\Product\Models\VendorProductPrice;
use Modules\Vendor\Models\Vendor;

class InventoryService
{
    /**
     * Adjust stock for a simple product (catalog-level stock).
     */
    public function adjustProductStock(
        Product $product,
        int $quantity,
        string $type = 'adjustment',
        ?string $notes = null,
        ?Vendor $vendor = null,
        ?Warehouse $warehouse = null,
        ?User $createdBy = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Product {
        return DB::transaction(function () use ($product, $quantity, $type, $notes, $vendor, $warehouse, $createdBy, $referenceType, $referenceId) {
            $before = $product->stock_quantity;
            $after  = max(0, $before + $quantity);

            // Create inventory log
            InventoryLog::create([
                'product_id'     => $product->id,
                'vendor_id'      => $vendor?->id,
                'warehouse_id'   => $warehouse?->id,
                'type'           => $type,
                'quantity'       => $quantity,
                'stock_before'   => $before,
                'stock_after'    => $after,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
                'created_by'     => $createdBy?->id,
            ]);

            // Update product stock
            $product->update([
                'stock_quantity' => $after,
                'stock_status'   => $after > 0 ? 'in_stock' : 'out_of_stock',
                'total_sold'     => $quantity < 0 ? $product->total_sold + abs($quantity) : $product->total_sold,
            ]);

            // Dispatch event
            StockAdjusted::dispatch($product, $quantity, $before, $after, $type);

            // Check low-stock threshold
            if ($after <= $product->low_stock_threshold && $after > 0) {
                $this->notifyLowStock($product, $vendor);
            }

            return $product->fresh();
        });
    }

    /**
     * Adjust stock for a product variant.
     */
    public function adjustVariantStock(
        ProductVariant $variant,
        int $quantity,
        string $type = 'adjustment',
        ?string $notes = null,
        ?Vendor $vendor = null,
        ?Warehouse $warehouse = null,
        ?User $createdBy = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ProductVariant {
        return DB::transaction(function () use ($variant, $quantity, $type, $notes, $vendor, $warehouse, $createdBy, $referenceType, $referenceId) {
            $before = $variant->stock_quantity;
            $after  = max(0, $before + $quantity);

            InventoryLog::create([
                'product_id'     => $variant->product_id,
                'variant_id'     => $variant->id,
                'vendor_id'      => $vendor?->id,
                'warehouse_id'   => $warehouse?->id,
                'type'           => $type,
                'quantity'       => $quantity,
                'stock_before'   => $before,
                'stock_after'    => $after,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
                'created_by'     => $createdBy?->id,
            ]);

            $variant->update([
                'stock_quantity' => $after,
            ]);

            // Also update parent product's total variant stock
            $product = $variant->product;
            $totalVariantStock = $product->variants()->sum('stock_quantity');
            $product->update([
                'stock_quantity' => $totalVariantStock,
                'stock_status'   => $totalVariantStock > 0 ? 'in_stock' : 'out_of_stock',
            ]);

            StockAdjusted::dispatch($variant->product, $quantity, $before, $after, $type, $variant);

            return $variant->fresh();
        });
    }

    /**
     * Adjust stock for a vendor-specific product price entry.
     */
    public function adjustVendorProductStock(
        VendorProductPrice $vendorProduct,
        int $quantity,
        string $type = 'adjustment',
        ?string $notes = null,
        ?Warehouse $warehouse = null,
        ?User $createdBy = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): VendorProductPrice {
        return DB::transaction(function () use ($vendorProduct, $quantity, $type, $notes, $warehouse, $createdBy, $referenceType, $referenceId) {
            $before = $vendorProduct->stock_quantity;
            $after  = max(0, $before + $quantity);

            InventoryLog::create([
                'product_id'     => $vendorProduct->product_id,
                'vendor_id'      => $vendorProduct->vendor_id,
                'warehouse_id'   => $warehouse?->id,
                'type'           => $type,
                'quantity'       => $quantity,
                'stock_before'   => $before,
                'stock_after'    => $after,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'notes'          => $notes,
                'created_by'     => $createdBy?->id,
            ]);

            $vendorProduct->update([
                'stock_quantity' => $after,
                'stock_status'   => $after > 0 ? 'in_stock' : 'out_of_stock',
            ]);

            return $vendorProduct->fresh();
        });
    }

    /**
     * Transfer stock between warehouses.
     */
    public function transferStock(
        Product $product,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        int $quantity,
        ?User $createdBy = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $quantity, $createdBy, $notes) {
            // Log transfer out
            InventoryLog::create([
                'product_id'   => $product->id,
                'vendor_id'    => $fromWarehouse->vendor_id,
                'warehouse_id' => $fromWarehouse->id,
                'type'         => 'transfer_out',
                'quantity'     => -$quantity,
                'stock_before' => 0,
                'stock_after'  => 0,
                'notes'        => $notes ?? "Transfer to {$toWarehouse->name}",
                'created_by'   => $createdBy?->id,
            ]);

            // Log transfer in
            InventoryLog::create([
                'product_id'   => $product->id,
                'vendor_id'    => $toWarehouse->vendor_id,
                'warehouse_id' => $toWarehouse->id,
                'type'         => 'transfer_in',
                'quantity'     => $quantity,
                'stock_before' => 0,
                'stock_after'  => 0,
                'notes'        => $notes ?? "Transfer from {$fromWarehouse->name}",
                'created_by'   => $createdBy?->id,
            ]);
        });
    }

    /**
     * Get stock history for a product/vendor combination.
     */
    public function getStockHistory(
        int $productId,
        ?int $vendorId = null,
        ?int $limit = 50
    ) {
        $query = InventoryLog::with(['createdBy', 'warehouse'])
            ->where('product_id', $productId)
            ->latest();

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get low-stock products for a vendor.
     */
    public function getLowStockProducts(int $vendorId, ?int $threshold = null)
    {
        $query = VendorProductPrice::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->where('manage_stock', true)
            ->where('stock_quantity', '>', 0)  // Not yet out of stock
            ->with('product');

        if ($threshold) {
            $query->where('stock_quantity', '<=', $threshold);
        }

        return $query->get();
    }

    /**
     * Get out-of-stock products for a vendor.
     */
    public function getOutOfStockProducts(int $vendorId)
    {
        return VendorProductPrice::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->where('stock_status', 'out_of_stock')
            ->with('product')
            ->get();
    }

    /**
     * Get stock summary for a vendor (total, low, out of stock counts).
     */
    public function getStockSummary(int $vendorId): array
    {
        $total = VendorProductPrice::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->count();

        $lowStock = VendorProductPrice::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $outOfStock = VendorProductPrice::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->where('stock_status', 'out_of_stock')
            ->count();

        return [
            'total_products' => $total,
            'low_stock'      => $lowStock,
            'out_of_stock'   => $outOfStock,
            'in_stock'       => $total - $lowStock - $outOfStock,
        ];
    }

    /**
     * Dispatch low-stock notification to the vendor.
     */
    private function notifyLowStock(Product $product, ?Vendor $vendor = null): void
    {
        if ($vendor && $vendor->user) {
            try {
                $vendor->user->notify(
                    new LowStockNotification($product)
                );
            } catch (Exception $e) {
                Log::warning("Low stock notification failed: {$e->getMessage()}");
            }
        }
    }
}
