<?php

namespace Modules\Affiliate\Models;

use Modules\Affiliate\Models\AffiliateProduct;
use Modules\Auth\Models\User;
use Modules\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateClick extends Model
{
    use HasUuid;

    protected $fillable = [
        'affiliate_product_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referrer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(AffiliateProduct::class, 'affiliate_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
