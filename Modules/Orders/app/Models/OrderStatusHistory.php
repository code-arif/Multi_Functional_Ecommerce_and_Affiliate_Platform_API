<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by',
        'changed_by_name',
        'notes',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
