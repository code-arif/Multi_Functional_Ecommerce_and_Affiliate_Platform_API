<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class Invoice extends Model
{
    use HasUuid;

    protected $fillable = [
        'invoice_number',
        'order_id',
        'subtotal',
        'shipping_charge',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'status',
        'notes',
        'issued_at',
        'paid_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'issued_at'       => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = config('orders.invoice_number_prefix', 'INV') . '-' . now()->format('Y') . '-';
        $last = static::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = $last ? ((int) substr($last, -6)) + 1 : 1;

        return sprintf('%s%06d', $prefix, $sequence);
    }
}
