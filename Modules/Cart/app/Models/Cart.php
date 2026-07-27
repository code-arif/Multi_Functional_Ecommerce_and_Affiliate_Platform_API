<?php

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Cart extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'notes',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cart $cart) {
            if (empty($cart->session_id)) {
                $cart->session_id = Str::random(40);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(\Modules\Promotions\Models\Coupon::class);
    }

    public function getSubtotalAttribute()
    {
        return $this->items->sum(fn($item) => $item->total);
    }

    public function getTotalAttribute()
    {
        return max(0, $this->subtotal - ($this->discount_amount ?? 0));
    }

    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getIsEmptyAttribute(): bool
    {
        return $this->items->isEmpty();
    }
}
