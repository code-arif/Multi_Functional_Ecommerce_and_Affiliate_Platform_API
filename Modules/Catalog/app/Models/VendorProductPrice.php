<?php

namespace Modules\Catalog\Models;

use Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class VendorProductPrice extends Model
{
    use HasUuid;
    protected $table = 'vendor_product_prices';

    protected $fillable = [
        'vendor_id',
        'product_id',
        'price',
        'sale_price',
        'stock_quantity',
        'low_stock_threshold',
        'manage_stock',
        'stock_status',
        'is_active',
    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'sale_price'        => 'decimal:2',
        'stock_quantity'    => 'integer',
        'low_stock_threshold' => 'integer',
        'manage_stock'      => 'boolean',
        'is_active'         => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock_status === 'in_stock' && $this->stock_quantity > 0;
    }
}
