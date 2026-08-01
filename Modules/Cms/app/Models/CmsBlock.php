<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Traits\HasUuid;

class CmsBlock extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'content',
        'data',
        'is_active',
        'order',
    ];

    protected $casts = [
        'data'      => 'array',
        'is_active' => 'boolean',
        'order'     => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
