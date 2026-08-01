<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;

class Currency extends Model
{
    use HasUuid;
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'exchange_rate',
        'precision',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:8',
        'precision'     => 'integer',
        'is_default'    => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
