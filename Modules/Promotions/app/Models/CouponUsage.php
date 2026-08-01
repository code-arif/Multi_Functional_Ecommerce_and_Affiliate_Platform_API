<?php

namespace Modules\Promotions\Models;

use Modules\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasUuid;

    protected $fillable = ['coupon_id', 'user_id', 'order_id', 'discount_amount'];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function order()
    {
        return $this->belongsTo(\Modules\Orders\Models\Order::class);
    }
}
