<?php

namespace Modules\Payments\Models;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'payment_id',
        'order_id',
        'refund_transaction_id',
        'amount',
        'fee',
        'currency',
        'reason',
        'status',
        'processed_by',
        'processed_at',
        'gateway_response',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'fee'              => 'decimal:2',
        'gateway_response' => 'array',
        'processed_at'     => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Orders\Models\Order::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
