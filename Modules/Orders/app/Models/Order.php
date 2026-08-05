<?php

namespace Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Traits\HasUuid;
use Modules\Orders\Database\Factories\OrderFactory;
use Modules\Orders\Enums\OrderStatus;
use Modules\Orders\Enums\PaymentStatus;
use Modules\Payments\Models\Payment;
use Modules\Vendor\Models\Vendor;

class Order extends Model
{
    use HasUuid;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'group_id',
        'order_number',
        'subtotal',
        'shipping_charge',
        'discount_amount',
        'coupon_discount',
        'tax_amount',
        'total_amount',
        'coupon_id',
        'coupon_code',
        'payment_method',
        'payment_status',
        'shipping_method',
        'shipping_name',
        'shipping_phone',
        'shipping_email',
        'shipping_address_line1',
        'shipping_address_line2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'shipping_address',
        'billing_address',
        'customer_note',
        'admin_note',
        'status',
        'tracking_number',
        'shipping_carrier',
        'tracking_token',
        'guest_email',
        'guest_token',
        'cancel_reason',
        'confirmed_at',
        'processed_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected $casts = [
        'subtotal'         => 'decimal:2',
        'shipping_charge'  => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'coupon_discount'  => 'decimal:2',
        'tax_amount'       => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'confirmed_at'     => 'datetime',
        'processed_at'     => 'datetime',
        'paid_at'          => 'datetime',
        'shipped_at'       => 'datetime',
        'delivered_at'     => 'datetime',
        'cancelled_at'     => 'datetime',
        'refunded_at'      => 'datetime',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function cancelRequests(): HasMany
    {
        return $this->hasMany(CancelRequest::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Other vendor sub-orders created in the same checkout group.
     */
    public function groupOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'group_id', 'group_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForVendor(Builder $query, ?int $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeByPaymentStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('payment_status', $status) : $query;
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('order_number', 'like', "%{$term}%")
                ->orWhere('shipping_name', 'like', "%{$term}%")
                ->orWhere('shipping_phone', 'like', "%{$term}%")
                ->orWhere('guest_email', 'like', "%{$term}%");
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────────────────

    public function getCanBeCancelledAttribute(): bool
    {
        return OrderStatus::fromValue($this->status)?->isCancelable() ?? false;
    }

    public function getCanBeCustomerCancelledAttribute(): bool
    {
        return OrderStatus::fromValue($this->status)?->isCustomerCancelable() ?? false;
    }

    public function getIsGuestOrderAttribute(): bool
    {
        return is_null($this->user_id);
    }

    public function getStatusLabelAttribute(): string
    {
        return OrderStatus::fromValue($this->status)?->label() ?? ucfirst((string) $this->status);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return PaymentStatus::fromValue($this->payment_status)?->label() ?? ucfirst((string) $this->payment_status);
    }

    public function getTotalAttribute(): float
    {
        return (float) ($this->total_amount ?? 0);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === PaymentStatus::Paid->value;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generators
    // ─────────────────────────────────────────────────────────────────────────

    public static function generateOrderNumber(): string
    {
        $prefix = config('orders.order_number_prefix', 'ORD') . '-' . now()->format('Y') . '-';
        $last = static::withTrashed()
            ->where('order_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('order_number');

        $sequence = $last ? ((int) substr($last, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $sequence);
    }

    public static function generateGroupId(): string
    {
        return (string) Str::uuid();
    }
}
