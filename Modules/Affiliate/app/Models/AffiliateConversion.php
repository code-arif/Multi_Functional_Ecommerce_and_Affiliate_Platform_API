<?php

namespace Modules\Affiliate\Models;

use Modules\Affiliate\Models\AffiliateProduct;
use App\Models\User;
use Modules\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class AffiliateConversion extends Model
{
    use HasUuid;
    protected $fillable = [
        'affiliate_product_id',
        'user_id',
        'order_id',
        'order_amount',
        'commission_amount',
        'status',
        'ip_address',
        'referrer',
        'converted_at',
    ];

    protected $casts = [
        'order_amount'      => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'converted_at'      => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(AffiliateProduct::class, 'affiliate_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }
}
