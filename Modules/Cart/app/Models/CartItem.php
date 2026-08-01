<?php

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class CartItem extends Model
{
    use HasUuid;
    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(\Modules\Catalog\Models\Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(\Modules\Catalog\Models\ProductVariant::class);
    }

    public function getTotalAttribute()
    {
        return $this->unit_price * $this->quantity;
    }
}
