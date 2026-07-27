<?php

namespace Modules\Finance\Models;

use Modules\Vendor\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorSettlement extends Model
{
    protected $table = 'vendor_settlements';

    protected $fillable = [
        'vendor_id',
        'period_label',
        'period_start',
        'period_end',
        'total_sales',
        'total_commission',
        'net_earnings',
        'total_paid',
        'balance_carried',
        'status',
        'finalized_at',
    ];

    protected $casts = [
        'total_sales'      => 'decimal:2',
        'total_commission' => 'decimal:2',
        'net_earnings'     => 'decimal:2',
        'total_paid'       => 'decimal:2',
        'balance_carried'  => 'decimal:2',
        'period_start'     => 'date',
        'period_end'       => 'date',
        'finalized_at'     => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getIsFinalizedAttribute(): bool
    {
        return $this->status === 'finalized';
    }
}
