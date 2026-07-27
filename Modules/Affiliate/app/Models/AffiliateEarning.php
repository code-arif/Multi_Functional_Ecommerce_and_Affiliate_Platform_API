<?php

namespace Modules\Affiliate\Models;

use Modules\Affiliate\Models\AffiliateProduct;
use Modules\Auth\Models\User;
use Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateEarning extends Model
{
    protected $fillable = [
        'user_id',
        'affiliate_product_id',
        'order_id',
        'amount',
        'type',
        'status',
        'notes',
        'available_at',
        'paid_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'available_at' => 'datetime',
        'paid_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(AffiliateProduct::class, 'affiliate_product_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'available';
    }
}
