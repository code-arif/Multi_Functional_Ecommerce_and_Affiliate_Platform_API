<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class VendorAddress extends Model
{
    use HasUuid;
    protected $fillable = [
        'vendor_id',
        'label',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'latitude'   => 'decimal:7',
        'longitude'  => 'decimal:7',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
