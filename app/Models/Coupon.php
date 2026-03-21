<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'value'                 => 'decimal:2',
        'minimum_order_amount'  => 'decimal:2',
        'maximum_discount'      => 'decimal:2',
        'is_active'             => 'boolean',
        'starts_at'             => 'datetime',
        'expires_at'            => 'datetime',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsUsageLimitReachedAttribute(): bool
    {
        return $this->usage_limit && $this->used_count >= $this->usage_limit;
    }

    public function isValidForUser(int $userId): bool
    {
        $userUsageCount = $this->usages()->where('user_id', $userId)->count();
        return $userUsageCount < $this->usage_limit_per_user;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < $this->minimum_order_amount) return 0;

        $discount = $this->type === 'percentage'
            ? ($subtotal * $this->value / 100)
            : $this->value;

        if ($this->maximum_discount) {
            $discount = min($discount, $this->maximum_discount);
        }

        return round($discount, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
