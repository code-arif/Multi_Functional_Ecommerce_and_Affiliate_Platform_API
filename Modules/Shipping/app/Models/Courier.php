<?php

namespace Modules\Shipping\Models;

use Modules\Vendor\Models\Vendor;
use Modules\Shipping\Models\ShippingRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Courier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'website',
        'tracking_url_template',
        'contact_phone',
        'contact_email',
        'supported_services',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'supported_services' => 'array',
        'is_active'          => 'boolean',
        'sort_order'         => 'integer',
    ];

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ─── Relationships ────────────────────────────────────────────

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    // ─── Boot ─────────────────────────────────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $courier) {
            if (empty($courier->slug)) {
                $courier->slug = Str::slug($courier->name);
            }
        });
    }

    // ─── Accessors ────────────────────────────────────────────────

    public function getTrackingUrlAttribute(): ?string
    {
        if (!$this->tracking_url_template || !$this->carrier_tracking_code) {
            return null;
        }
        return str_replace('{tracking_number}', $this->carrier_tracking_code, $this->tracking_url_template);
    }
}
