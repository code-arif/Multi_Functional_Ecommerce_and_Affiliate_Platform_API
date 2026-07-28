<?php

namespace Modules\Shipping\Models;

use Modules\Shipping\Models\Shipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingHistory extends Model
{
    protected $fillable = [
        'shipment_id',
        'status',
        'location',
        'description',
        'tracked_at',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('tracked_at', 'desc');
    }
}
