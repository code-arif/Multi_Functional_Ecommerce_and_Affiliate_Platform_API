<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Cache\CacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInventoryUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private int $productId,
        private int $quantity,
        private string $action = 'decrement'
    ) {}

    public function handle(CacheService $cache): void
    {
        $product = Product::find($this->productId);
        if (!$product) return;

        if ($this->action === 'decrement') {
            $product->decrement('stock_quantity', $this->quantity);
            $product->increment('total_sold', $this->quantity);

            if ($product->stock_quantity <= 0) {
                $product->update(['stock_status' => 'out_of_stock']);
            } elseif ($product->stock_quantity <= $product->low_stock_threshold) {
                // Notify admin of low stock
                Log::channel('orders')
                    ->warning("Low stock alert: {$product->name}", [
                        'product_id' => $product->id,
                        'stock'      => $product->stock_quantity,
                    ]);
            }
        } elseif ($this->action === 'increment') {
            $product->increment('stock_quantity', $this->quantity);
            if ($product->stock_status === 'out_of_stock' && $product->stock_quantity > 0) {
                $product->update(['stock_status' => 'in_stock']);
            }
        }

        $cache->forgetProduct($product->slug);
    }

    public function queue(): string { return 'default'; }
}
