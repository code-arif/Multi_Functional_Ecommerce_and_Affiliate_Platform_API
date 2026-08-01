<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class ProductVariant extends Model
{
    use HasUuid;
    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'price',
        'sale_price',
        'stock_quantity',
        'weight',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price'      => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
