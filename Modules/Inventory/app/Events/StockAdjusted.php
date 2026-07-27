<?php

namespace Modules\Inventory\Events;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
