<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    protected $fillable = [
        'affiliate_product_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
    ];

    public function affiliateProduct(): BelongsTo
    {
        return $this->belongsTo(AffiliateProduct::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
