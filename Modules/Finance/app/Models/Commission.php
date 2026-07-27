<?php

namespace Modules\Finance\Models;

use Modules\Orders\Models\Order;
use Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    protected $fillable = [
        'order_id',
        'vendor_id',
        'order_total',
        'commission_rate',
        'commission_type',
        'commission_amount',
        'status',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'order_total'       => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'approved_at'       => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Calculate the commission amount based on order total and vendor rate.
     */
    public static function calculate(float $orderTotal, float $rate, string $type = 'percentage'): float
    {
        return match ($type) {
            'percentage' => round($orderTotal * ($rate / 100), 2),
            'fixed'      => round(min($rate, $orderTotal), 2),
            default      => round($orderTotal * ($rate / 100), 2),
        };
    }
}
