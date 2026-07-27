<?php

namespace Modules\Cart\Models;

use Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompareListItem extends Model
{
    protected $table = 'compare_list_items';

    protected $fillable = [
        'compare_list_id',
        'product_id',
    ];

    public function compareList(): BelongsTo
    {
        return $this->belongsTo(CompareList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
