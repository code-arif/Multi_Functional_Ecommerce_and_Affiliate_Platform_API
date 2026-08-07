<?php

namespace Modules\Catalog\Models;

use Modules\Catalog\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class Category extends Model
{
    use HasUuid;
    use HasSlug, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'banner',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'sort_order',
        'is_featured',
        'is_active',
        'status',
        'commission_rate',
        'depth',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
        'commission_rate' => 'decimal:2',
        'depth'       => 'integer',
        'created_by'  => 'integer',
        'updated_by'  => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (Category $category) {
            if ($category->parent_id) {
                $parent = self::find($category->parent_id);
                $category->depth = $parent ? $parent->depth + 1 : 0;
            } else {
                $category->depth = 0;
            }

            // Set created_by if auth user is logged in and not already set
            if (auth()->check() && !$category->created_by) {
                $category->created_by = auth()->id();
            }
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('parent_id')) {
                if ($category->parent_id) {
                    $parent = self::find($category->parent_id);
                    $category->depth = $parent ? $parent->depth + 1 : 0;
                } else {
                    $category->depth = 0;
                }
            }

            // Set updated_by if auth user is logged in
            if (auth()->check()) {
                $category->updated_by = auth()->id();
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) return null;
        return str_starts_with($this->image, 'http') ? $this->image : asset('storage/' . $this->image);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner) return null;
        return str_starts_with($this->banner, 'http') ? $this->banner : asset('storage/' . $this->banner);
    }
}
