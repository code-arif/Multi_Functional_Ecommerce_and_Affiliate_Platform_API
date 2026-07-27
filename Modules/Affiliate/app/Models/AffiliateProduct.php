<?php

namespace Modules\Affiliate\Models;

use Modules\Affiliate\Models\AffiliateClick;
use Modules\Affiliate\Models\AffiliateConversion;
use Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'images',
        'display_price',
        'affiliate_link',
        'commission_type',
        'commission_value',
        'source_platform',
        'click_count',
        'is_featured',
        'is_active',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'images'         => 'array',
        'display_price'  => 'decimal:2',
        'commission_value' => 'decimal:2',
        'click_count'    => 'integer',
        'is_featured'    => 'boolean',
        'is_active'      => 'boolean',
        'sort_order'     => 'integer',
    ];

    // ─── Relationships ─────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class, 'affiliate_product_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(AffiliateConversion::class, 'affiliate_product_id');
    }

    // ─── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('source_platform', $platform);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->latest();
    }

    // ─── Accessors ─────────────────────────────────────────────────

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) return null;
        return str_starts_with($this->thumbnail, 'http')
            ? $this->thumbnail
            : asset('storage/' . $this->thumbnail);
    }

    public function getCurrentPriceAttribute()
    {
        return $this->display_price;
    }

    public function getIsOnCommissionAttribute(): bool
    {
        return $this->commission_value > 0;
    }

    public function getCommissionLabelAttribute(): string
    {
        return $this->commission_type === 'percentage'
            ? "{$this->commission_value}%"
            : "-{$this->commission_value}";
    }
}
