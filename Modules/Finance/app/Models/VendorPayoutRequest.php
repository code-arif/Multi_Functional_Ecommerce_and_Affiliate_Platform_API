<?php

namespace Modules\Finance\Models;

use Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class VendorPayoutRequest extends Model
{
    use HasUuid;
    protected $table = 'vendor_payout_requests';

    protected $fillable = [
        'vendor_id',
        'amount',
        'balance_before',
        'balance_after',
        'payment_method',
        'payment_details',
        'notes',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
        'approved_at'    => 'datetime',
        'completed_at'   => 'datetime',
    ];

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
        return $query->whereIn('status', ['approved', 'completed']);
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }
}
