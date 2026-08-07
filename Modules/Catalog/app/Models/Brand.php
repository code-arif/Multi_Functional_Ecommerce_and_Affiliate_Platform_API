<?php

namespace Modules\Catalog\Models;

use Modules\Catalog\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class Brand extends Model
{
    use HasUuid;
    use HasSlug, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'banner',
        'website',
        'meta_title',
        'meta_description',
        'sort_order',
        'is_active',
        'is_featured',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'sort_order'  => 'integer',
        'created_by'  => 'integer',
        'updated_by'  => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (Brand $brand) {
            // Set created_by if auth user is logged in
            if (auth()->check()) {
                if (!$brand->created_by) {
                    $brand->created_by = auth()->id();
                }

                // If not admin/moderator, force status to pending
                if (!auth()->user()->isAdmin() && !auth()->user()->isModerator()) {
                    $brand->status = 'pending';
                }
            }
        });

        static::updating(function (Brand $brand) {
            // Set updated_by if auth user is logged in
            if (auth()->check()) {
                $brand->updated_by = auth()->id();
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) return null;
        return str_starts_with($this->logo, 'http') ? $this->logo : asset('storage/' . $this->logo);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner) return null;
        return str_starts_with($this->banner, 'http') ? $this->banner : asset('storage/' . $this->banner);
    }
}
