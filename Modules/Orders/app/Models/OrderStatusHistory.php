<?php

namespace Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class OrderStatusHistory extends Model
{
    use HasUuid;

    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'actor_type',
        'changed_by',
        'changed_by_name',
        'notes',
        'notify_customer',
    ];

    protected $casts = [
        'notify_customer' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
