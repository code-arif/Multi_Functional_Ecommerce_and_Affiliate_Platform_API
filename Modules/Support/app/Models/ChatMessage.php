<?php
namespace Modules\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ChatMessage extends Model
{
    use HasUuid;

    protected $fillable = [
        'chat_room_id',
        'sender_id',
        'message',
        'attachment',
        'attachment_type',
        'is_read',
        'type',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'type' => 'string',
    ];

    public function room()
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

     public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) return null;
        return asset('storage/' . $this->attachment);
    }
}
