<?php

namespace Modules\Support\Classes\Services;

use App\Models\User;
use Modules\Support\Models\ChatMessage;
use Modules\Support\Models\ChatRoom;

class ChatService
{
    public function createRoom(User $user, array $data): ChatRoom
    {
        return ChatRoom::create([
            'user_id'  => $user->id,
            'order_id' => $data['order_id'] ?? null,
            'subject'  => $data['subject'] ?? 'General Inquiry',
            'status'   => 'open',
        ]);
    }

    public function getMessages(ChatRoom $room, int $limit = 50)
    {
        return $room->messages()->with('user')->latest()->limit($limit)->get()->reverse();
    }

    public function sendMessage(ChatRoom $room, User $user, string $message, string $type = 'text'): ChatMessage
    {
        $msg = $room->messages()->create([
            'sender_id' => $user->id,
            'message'   => $message,
            'type'      => $type,
            'is_read'   => false,
        ]);

        $room->update(['last_message_at' => now()]);

        return $msg->load('user');
    }

    public function closeRoom(ChatRoom $room): ChatRoom
    {
        $room->update(['status' => 'closed']);
        return $room->fresh();
    }

    public function getAdminRooms()
    {
        return ChatRoom::with('user')
            ->where('status', 'active')
            ->latest('last_message_at')
            ->get();
    }
}
