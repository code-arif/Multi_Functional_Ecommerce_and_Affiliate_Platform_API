<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasSlug, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'type',
        'price',
        'sale_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'manage_stock',
        'stock_status',
        'short_description',
        'description',
        'thumbnail',
        'average_rating',
        'total_reviews',
        'total_sold',
        'weight',
        'weight_unit',
        'tags',
        'is_featured',
        'is_new',
        'is_bestseller',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'published_at',
    ];

    protected $casts = [
        'price'               => 'decimal:2',
        'sale_price'          => 'decimal:2',
        'cost_price'          => 'decimal:2',
        'average_rating'      => 'decimal:2',
        'manage_stock'        => 'boolean',
        'is_featured'         => 'boolean',
        'is_new'              => 'boolean',
        'is_bestseller'       => 'boolean',
        'tags'                => 'array',
        'published_at'        => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlistedByUsers(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('status', 'active');
    }

    public function scopeNew($query)
    {
        return $query->where('is_new', true)->where('status', 'active');
    }

    public function scopeBestseller($query)
    {
        return $query->where('is_bestseller', true)->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_status', 'in_stock');
    }

    // ─── Accessors ────────────────────────────────────────────────

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) return null;
        return str_starts_with($this->thumbnail, 'http')
            ? $this->thumbnail
            : asset('storage/' . $this->thumbnail);
    }

    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->is_on_sale) return 0;
        return (int) round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->stock_status === 'in_stock' && $this->stock_quantity > 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function decrementStock(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
        $this->increment('total_sold', $quantity);

        if ($this->stock_quantity <= 0) {
            $this->update(['stock_status' => 'out_of_stock']);
        }
    }

    public function recalculateRating(): void
    {
        $avg   = $this->reviews()->avg('rating') ?? 0;
        $total = $this->reviews()->count();
        $this->update([
            'average_rating' => round($avg, 2),
            'total_reviews'  => $total,
        ]);
    }

    /**
     * Generate new slug for product
     */
    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name); // base slug
        $originalSlug = $slug;
        $count = 1;

        while (
            static::where('slug', $slug)
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
