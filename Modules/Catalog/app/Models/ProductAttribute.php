<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasUuid;

class ProductAttribute extends Model
{
    use HasUuid;
    protected $fillable = ['product_id', 'name', 'sort_order'];

    public function values(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_attribute_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
