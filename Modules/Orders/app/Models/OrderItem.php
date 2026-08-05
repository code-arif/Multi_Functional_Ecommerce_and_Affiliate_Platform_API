<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;

class OrderItem extends Model
{
    use HasUuid;
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_sku',
        'product_image',
        'quantity',
        'unit_price',
        'total_price',
        'vendor_id',
    ];

    protected $casts = [
        'variant_attributes' => 'array',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
