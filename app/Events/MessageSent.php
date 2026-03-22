<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("chat.room.{$this->message->chat_room_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'chat_room_id'    => $this->message->chat_room_id,
            'message'         => $this->message->message,
            'attachment_url'  => $this->message->attachment_url,
            'attachment_type' => $this->message->attachment_type,
            'sender'          => [
                'id'   => $this->message->sender->id,
                'name' => $this->message->sender->name,
            ],
            'created_at'      => $this->message->created_at->toDateTimeString(),
        ];
    }
}
