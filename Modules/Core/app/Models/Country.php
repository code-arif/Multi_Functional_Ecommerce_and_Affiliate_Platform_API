<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\HasUuid;

class Country extends Model
{
    use HasUuid;
    protected $fillable = [
        'name',
        'iso2',
        'iso3',
        'phone_code',
        'currency_code',
        'currency_symbol',
        'flag',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_default'  => 'boolean',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function getFlagUrlAttribute(): ?string
    {
        if (!$this->flag) return null;
        return asset('storage/' . $this->flag);
    }
}
