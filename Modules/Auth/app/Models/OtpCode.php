<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'channel',
        'destination',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }

    public function scopeValid($query)
    {
        return $query->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at?->isPast() ?? true;
    }

    public function getIsUsedAttribute(): bool
    {
        return !is_null($this->used_at);
    }

    public function getIsValidAttribute(): bool
    {
        return !$this->is_used && !$this->is_expired;
    }
}
