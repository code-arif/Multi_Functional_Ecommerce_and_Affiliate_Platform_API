<?php

namespace Modules\Shipping\Models;

use Modules\Shipping\Models\Courier;
use Modules\Shipping\Models\ShippingZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class ShippingRate extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'shipping_zone_id',
        'courier_id',
        'name',
        'method',
        'base_rate',
        'rate_per_kg',
        'rate_per_item',
        'free_shipping_min',
        'max_weight',
        'estimated_days_min',
        'estimated_days_max',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'base_rate'          => 'decimal:2',
        'rate_per_kg'        => 'decimal:2',
        'rate_per_item'      => 'decimal:2',
        'free_shipping_min'  => 'decimal:2',
        'max_weight'         => 'decimal:2',
        'estimated_days_min' => 'integer',
        'estimated_days_max' => 'integer',
        'conditions'         => 'array',
        'is_active'          => 'boolean',
    ];

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    // ─── Relationships ────────────────────────────────────────────

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    // ─── Calculations ─────────────────────────────────────────────

    /**
     * Calculate shipping cost for given weight and item count.
     */
    public function calculateCost(float $weight = 0, int $itemCount = 1): float
    {
        return $this->base_rate
            + ($weight * $this->rate_per_kg)
            + ($itemCount * $this->rate_per_item);
    }

    /**
     * Check if shipping is free for a given subtotal.
     */
    public function isFreeShipping(float $subtotal): bool
    {
        return $this->free_shipping_min !== null && $subtotal >= $this->free_shipping_min;
    }

    /**
     * Get estimated delivery range as string.
     */
    public function getEstimatedDeliveryText(): string
    {
        if (!$this->estimated_days_min) return 'N/A';
        if ($this->estimated_days_min === $this->estimated_days_max) {
            return "{$this->estimated_days_min} day(s)";
        }
        return "{$this->estimated_days_min}-{$this->estimated_days_max} day(s)";
    }
}
