<?php

namespace Modules\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

class TicketMessage extends Model
{
    use HasUuid;
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachments',
        'is_staff_reply',
    ];

    protected $casts = [
        'attachments'   => 'array',
        'is_staff_reply' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
