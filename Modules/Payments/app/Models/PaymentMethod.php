<?php

namespace Modules\Payments\Models;

use Modules\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class PaymentMethod extends Model
{
    use HasUuid;
    protected $table = 'payment_methods';

    protected $fillable = [
        'user_id',
        'gateway',
        'gateway_method_id',
        'type',
        'label',
        'details',
        'is_default',
    ];

    protected $casts = [
        'details'    => 'array',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
