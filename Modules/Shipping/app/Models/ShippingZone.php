<?php

namespace Modules\Shipping\Models;

use Modules\Shipping\Models\ShippingRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShippingZone extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'countries',
        'states',
        'cities',
        'postal_codes',
        'is_active',
    ];

    protected $casts = [
        'countries'    => 'array',
        'states'       => 'array',
        'cities'       => 'array',
        'postal_codes' => 'array',
        'is_active'    => 'boolean',
    ];

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Relationships ────────────────────────────────────────────

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    // ─── Boot ─────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $zone) {
            if (empty($zone->slug)) {
                $zone->slug = Str::slug($zone->name);
            }
        });
    }

    // ─── Query Helpers ────────────────────────────────────────────

    /**
     * Check if this zone covers a given country, state, or city.
     */
    public function coversLocation(?string $country, ?string $state = null, ?string $city = null): bool
    {
        if ($this->countries && !in_array($country, $this->countries)) {
            return false;
        }
        if ($this->states && $state && !in_array($state, $this->states)) {
            return false;
        }
        if ($this->cities && $city && !in_array($city, $this->cities)) {
            return false;
        }
        return true;
    }
}
