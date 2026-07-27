<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Medium extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'disk',
        'directory',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'width',
        'height',
        'extension',
        'mediable_type',
        'mediable_id',
        'uploaded_by',
        'is_public',
    ];

    protected $casts = [
        'file_size'  => 'integer',
        'width'      => 'integer',
        'height'     => 'integer',
        'is_public'  => 'boolean',
    ];

    public function mediable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class, 'uploaded_by');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, string $mimeType)
    {
        return $query->where('mime_type', 'like', "{$mimeType}%");
    }

    public function getUrlAttribute(): string
    {
        $path = $this->directory
            ? "{$this->directory}/{$this->file_name}"
            : $this->file_name;
        return asset("storage/{$path}");
    }

    public function getPathAttribute(): string
    {
        return $this->directory
            ? "{$this->directory}/{$this->file_name}"
            : $this->file_name;
    }
}
