<?php

namespace Modules\Shipping\Models;

use Modules\Vendor\Models\Vendor;
use Modules\Shipping\Models\Courier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PickupRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'courier_id',
        'status',
        'pickup_date',
        'pickup_time_from',
        'pickup_time_to',
        'address',
        'contact_name',
        'contact_phone',
        'notes',
        'parcels',
        'reference_code',
        'scheduled_at',
        'picked_up_at',
        'cancelled_at',
    ];

    protected $casts = [
        'parcels'         => 'array',
        'pickup_date'     => 'date',
        'pickup_time_from' => 'datetime:H:i',
        'pickup_time_to'  => 'datetime:H:i',
        'scheduled_at'    => 'datetime',
        'picked_up_at'    => 'datetime',
        'cancelled_at'    => 'datetime',
    ];

    // ─── Status Constants ─────────────────────────────────────────

    const STATUS_PENDING    = 'pending';
    const STATUS_SCHEDULED  = 'scheduled';
    const STATUS_PICKED_UP  = 'picked_up';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_FAILED     = 'failed';

    // ─── Relationships ────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    // ─── Boot ─────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $request) {
            if (empty($request->reference_code)) {
                $request->reference_code = 'PICK-' . strtoupper(Str::random(8));
            }
        });
    }
}
