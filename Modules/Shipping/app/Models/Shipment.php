<?php

namespace Modules\Shipping\Models;

use Modules\Orders\Models\Order;
use Modules\Vendor\Models\Vendor;
use Modules\Shipping\Models\Courier;
use Modules\Shipping\Models\ShippingRate;
use Modules\Shipping\Models\TrackingHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'vendor_id',
        'courier_id',
        'shipping_rate_id',
        'tracking_number',
        'carrier_tracking_code',
        'status',
        'method',
        'weight',
        'shipping_cost',
        'sender_name',
        'sender_phone',
        'sender_address',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'notes',
        'meta',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'weight'        => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'meta'          => 'array',
        'shipped_at'    => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    // ─── Status Constants ─────────────────────────────────────────

    const STATUS_PENDING       = 'pending';
    const STATUS_PROCESSING    = 'processing';
    const STATUS_PICKED_UP     = 'picked_up';
    const STATUS_IN_TRANSIT    = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED     = 'delivered';
    const STATUS_FAILED        = 'failed';
    const STATUS_RETURNED      = 'returned';
    const STATUS_CANCELLED     = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_PICKED_UP,
        self::STATUS_IN_TRANSIT,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_FAILED,
        self::STATUS_RETURNED,
        self::STATUS_CANCELLED,
    ];

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    // ─── Relationships ────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    public function trackingHistories(): HasMany
    {
        return $this->hasMany(TrackingHistory::class);
    }

    // ─── Status Transitions ───────────────────────────────────────

    public function markAsShipped(?string $trackingNumber = null): void
    {
        $this->update([
            'status'             => self::STATUS_IN_TRANSIT,
            'tracking_number'    => $trackingNumber ?? $this->tracking_number,
            'shipped_at'         => now(),
        ]);

        $this->addTrackingEntry(self::STATUS_IN_TRANSIT, 'Shipment is in transit.');
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status'       => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        $this->addTrackingEntry(self::STATUS_DELIVERED, 'Package has been delivered.');
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes'  => $reason,
        ]);

        $this->addTrackingEntry(self::STATUS_FAILED, $reason);
    }

    public function addTrackingEntry(string $status, string $description, ?string $location = null): TrackingHistory
    {
        return $this->trackingHistories()->create([
            'status'      => $status,
            'description' => $description,
            'location'    => $location,
            'tracked_at'  => now(),
        ]);
    }
}
