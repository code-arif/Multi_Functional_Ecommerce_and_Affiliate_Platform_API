<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_number',
        'subtotal',
        'shipping_cost',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'due_amount',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'total'         => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'due_amount'    => 'decimal:2',
        'issued_at'     => 'datetime',
        'due_at'        => 'datetime',
        'paid_at'       => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'overdue';
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }
}
