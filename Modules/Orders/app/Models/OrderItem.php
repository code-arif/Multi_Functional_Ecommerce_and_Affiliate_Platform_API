<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;
use Modules\Orders\Database\Factories\OrderItemFactory;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariant;
use Modules\Vendor\Models\Vendor;

class OrderItem extends Model
{
    use HasUuid;
    use HasFactory;

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'vendor_id',
        'product_name',
        'product_sku',
        'variant_attributes',
        'product_image',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'variant_attributes' => 'array',
        'unit_price'         => 'decimal:2',
        'quantity'           => 'integer',
        'subtotal'           => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
