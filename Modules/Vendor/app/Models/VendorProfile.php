<?php

namespace Modules\Vendor\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class VendorProfile extends Model
{
    use HasUuid;
    protected $fillable = [
        'vendor_id',
        'business_type',
        'business_registration_number',
        'tax_id',
        'website',
        'social_facebook',
        'social_instagram',
        'social_youtube',
        'return_policy',
        'shipping_policy',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'sort_order'  => 'integer',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
