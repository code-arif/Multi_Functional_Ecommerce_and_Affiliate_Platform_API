<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class Transaction extends Model
{
    use HasUuid;
    protected $fillable = [
        'payment_id',
        'order_id',
        'transaction_id',
        'type',
        'amount',
        'fee',
        'net',
        'currency',
        'status',
        'gateway_response',
        'notes',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'fee'              => 'decimal:2',
        'net'              => 'decimal:2',
        'gateway_response' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\Modules\Orders\Models\Order::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
