<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class ProductImage extends Model
{
    use HasUuid;
    protected $fillable = ['product_id', 'image_path', 'is_primary', 'sort_order'];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }
}
