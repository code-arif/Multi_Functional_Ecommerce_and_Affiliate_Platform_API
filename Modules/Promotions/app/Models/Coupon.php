<?php

namespace Modules\Promotions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
        'description',
    ];

    protected $casts = [
        'value'                => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount'     => 'decimal:2',
        'is_active'            => 'boolean',
        'starts_at'            => 'datetime',
        'expires_at'           => 'datetime',
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function getIsValidAttribute(): bool
    {
        return $this->is_active
            && $this->starts_at <= now()
            && ($this->expires_at === null || $this->expires_at > now())
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
}
