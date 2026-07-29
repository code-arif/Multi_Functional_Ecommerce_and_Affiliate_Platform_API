<?php

namespace Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'subject',
        'status',
        'assigned_to',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Auth\Models\User::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }
}
