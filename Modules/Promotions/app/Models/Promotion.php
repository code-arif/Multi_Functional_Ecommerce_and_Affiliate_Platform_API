<?php

namespace Modules\Promotions\Models;

use Modules\Product\Models\Product;;
use Modules\Catalog\Models\Category;
use Modules\Vendor\Models\Vendor;
use Modules\Orders\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class Promotion extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'discount_type',
        'discount_value',
        'maximum_discount',
        'min_quantity',
        'free_quantity',
        'discount_on',
        'tiers',
        'applies_to',
        'product_ids',
        'category_ids',
        'vendor_ids',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'is_active',
        'starts_at',
        'ends_at',
        'badge_text',
        'badge_color',
        'sort_order',
    ];

    protected $casts = [
        'discount_value'    => 'decimal:2',
        'maximum_discount'  => 'decimal:2',
        'tiers'             => 'array',
        'product_ids'       => 'array',
        'category_ids'      => 'array',
        'vendor_ids'        => 'array',
        'is_active'         => 'boolean',
        'starts_at'         => 'datetime',
        'ends_at'           => 'datetime',
    ];

    protected $appends = [
        'is_currently_active',
        'discount_label',
    ];

    // ─── Relationships ─────────────────────────────────────────────

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    // ─── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('ends_at', '<', now());
    }

    public function scopeFlashSales($query)
    {
        return $query->where('type', 'flash_sale')->active();
    }

    public function scopeSortByDisplay($query)
    {
        return $query->orderBy('sort_order')->latest();
    }

    // ─── Accessors ─────────────────────────────────────────────────

    public function getIsCurrentlyActiveAttribute(): bool
    {
        if (!$this->is_active) return false;

        $now = now();
        if ($this->starts_at && $this->starts_at > $now) return false;
        if ($this->ends_at && $this->ends_at <= $now) return false;

        return true;
    }

    public function getDiscountLabelAttribute(): string
    {
        return match ($this->type) {
            'flash_sale'    => $this->discount_type === 'percentage'
                ? "{$this->discount_value}% OFF"
                : "-{$this->discount_value}",
            'buy_x_get_y'   => "Buy {$this->min_quantity} Get {$this->free_quantity} Free",
            'tiered_discount' => 'Volume Discount',
            'seasonal'      => $this->discount_type === 'percentage'
                ? "{$this->discount_value}% OFF"
                : "-{$this->discount_value}",
            'free_shipping' => 'Free Shipping',
            default         => $this->name,
        };
    }

    public function getIsFlashSaleAttribute(): bool
    {
        return $this->type === 'flash_sale';
    }

    public function getIsBuyXGetYAttribute(): bool
    {
        return $this->type === 'buy_x_get_y';
    }

    public function getIsTieredAttribute(): bool
    {
        return $this->type === 'tiered_discount';
    }

    // ─── Eligibility Checks ───────────────────────────────────────

    /**
     * Check if this promotion applies to a specific product.
     */
    public function appliesToProduct(Product $product): bool
    {
        return match ($this->applies_to) {
            'all'       => true,
            'products'  => in_array($product->id, $this->product_ids ?? []),
            'categories' => $product->category_id && in_array($product->category_id, $this->category_ids ?? []),
            'vendors'   => $product->vendor_id && in_array($product->vendor_id, $this->vendor_ids ?? []),
            default     => false,
        };
    }

    /**
     * Get the tiered discount value for a given quantity.
     */
    public function getTieredDiscountValue(int $quantity): float
    {
        $tiers = $this->tiers ?? [];
        if (empty($tiers)) return $this->discount_value;

        // Sort tiers by from quantity descending
        usort($tiers, fn($a, $b) => $b['from'] <=> $a['from']);

        foreach ($tiers as $tier) {
            if ($quantity >= $tier['from']) {
                return (float) $tier['value'];
            }
        }

        return $this->discount_value;
    }

    /**
     * Check if usage limit has been reached.
     */
    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Check if a user has exceeded their per-user limit.
     */
    public function userHasExceededLimit(int $userId): bool
    {
        return $this->usages()->where('user_id', $userId)->count() >= $this->usage_per_user;
    }
}
