<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class CmsPage extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'template',
        'is_published',
        'published_at',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'order'        => 'integer',
    ];

    // ─── Scopes ────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    public function scopeScheduled($query)
    {
        return $query->where('is_published', true)
            ->where('published_at', '>', now());
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function scopeByTemplate($query, string $template)
    {
        return $query->where('template', $template);
    }

    // ─── Accessors ─────────────────────────────────────────────────

    public function getIsScheduledAttribute(): bool
    {
        return $this->is_published && $this->published_at && $this->published_at->isFuture();
    }

    public function getOgImageUrlAttribute(): ?string
    {
        if (!$this->og_image) return null;
        return str_starts_with($this->og_image, 'http')
            ? $this->og_image
            : asset('storage/' . $this->og_image);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->is_scheduled) return 'scheduled';
        if ($this->is_published) return 'published';
        return 'draft';
    }
}
