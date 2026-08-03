<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class ProductAttributeValue extends Model
{
    use HasUuid;
    protected $fillable = ['product_attribute_id', 'value', 'sort_order'];

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}
