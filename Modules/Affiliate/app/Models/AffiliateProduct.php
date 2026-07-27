<?php

namespace Modules\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'image',
        'affiliate_link',
        'commission_type',
        'commission_value',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'sale_price'       => 'decimal:2',
        'commission_value' => 'decimal:2',
        'is_featured'      => 'boolean',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }
}
