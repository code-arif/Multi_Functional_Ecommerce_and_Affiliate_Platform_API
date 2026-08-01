<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class City extends Model
{
    use HasUuid;
    protected $fillable = [
        'country_id',
        'state_id',
        'name',
        'code',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
