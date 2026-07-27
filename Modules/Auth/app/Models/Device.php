<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'device_name',
        'device_type',
        'platform',
        'browser',
        'ip_address',
        'user_agent',
        'push_token',
        'last_active_at',
        'is_trusted',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'is_trusted'     => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('last_active_at', '>=', now()->subDays(30));
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    public function updateLastActive(): void
    {
        $this->update(['last_active_at' => now()]);
    }
}
