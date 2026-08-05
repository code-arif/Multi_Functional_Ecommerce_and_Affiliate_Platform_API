<?php

namespace Modules\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;

class StockAdjusted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Product $product,
        public int $quantity,
        public int $stockBefore,
        public int $stockAfter,
        public string $type = 'adjustment',
        public ?ProductVariant $variant = null
    ) {}
}
