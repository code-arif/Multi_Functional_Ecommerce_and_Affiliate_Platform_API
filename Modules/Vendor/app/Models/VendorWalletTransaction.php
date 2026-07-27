<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;

class VendorWalletTransaction extends Model
{
    protected $fillable = [
        'vendor_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'status',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
