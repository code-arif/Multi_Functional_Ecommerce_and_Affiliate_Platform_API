<?php

namespace Modules\Orders\Models;

use Modules\Vendor\Models\Vendor;
use Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class Order extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'order_number',
        'subtotal',
        'shipping_cost',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'coupon_code',
        'coupon_discount',
        'payment_method',
        'payment_status',
        'shipping_method',
        'shipping_address',
        'shipping_name',
        'shipping_phone',
        'shipping_email',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'billing_address',
        'notes',
        'admin_note',
        'status',
        'tracking_token',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'shipping_cost'   => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'paid_at'         => 'datetime',
        'shipped_at'      => 'datetime',
        'delivered_at'    => 'datetime',
        'cancelled_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function cancelRequests(): HasMany
    {
        return $this->hasMany(CancelRequest::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function getTotalAttribute(): float
    {
        return (float) ($this->total_amount ?? 0);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getIsShippedAttribute(): bool
    {
        return $this->status === 'shipped';
    }

    public function getIsDeliveredAttribute(): bool
    {
        return $this->status === 'delivered';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }
}
