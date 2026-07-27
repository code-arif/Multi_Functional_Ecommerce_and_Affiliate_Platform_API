<?php

namespace Modules\Affiliate\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateClick extends Model
{
    protected $fillable = [
        'affiliate_product_id',
        'ip_address',
        'user_agent',
        'referrer',
    ];

    public function product()
    {
        return $this->belongsTo(AffiliateProduct::class, 'affiliate_product_id');
    }
}
