<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This references the notifications table for querying.
 * The actual model is from Illuminate\Notifications\DatabaseNotification.
 * This is for scoping/admin purposes.
 */
class Notification extends Model
{
    protected $table = 'notifications';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeByNotifiable($query, $notifiableType, $notifiableId)
    {
        return $query->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId);
    }
}
