<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class Payment extends Model
{
    use HasUuid;
    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_status',
        'gateway',
        'status',
        'transaction_id',
        'amount',
        'currency',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at'          => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Orders\Models\Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
