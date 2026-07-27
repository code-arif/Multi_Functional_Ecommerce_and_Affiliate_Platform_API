<?php

namespace Modules\Inventory\Listeners;

use Modules\Inventory\Events\StockAdjusted;
use Modules\Notifications\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Log;

class HandleLowStock
{
    public function handle(StockAdjusted $event): void
    {
        $product = $event->product;

        Log::info("Stock adjusted: {$product->name} ({$event->type}) - {$event->stockBefore} -> {$event->stockAfter}");

        // Notify if stock is low but not out of stock
        if ($event->stockAfter > 0 && $event->stockAfter <= $product->low_stock_threshold) {
            try {
                // Notify vendor if this is a vendor-managed product
                $vendorProduct = $product->vendorProductPrices()->first();
                if ($vendorProduct && $vendorProduct->vendor?->user) {
                    $vendorProduct->vendor->user->notify(new LowStockNotification($product));
                }
            } catch (\Exception $e) {
                Log::warning("Low stock notification failed: {$e->getMessage()}");
            }
        }
    }
}
