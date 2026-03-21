<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\ChatRoom;

class ChatService
{
    public function getOrCreateRoom(int $userId): ChatRoom
    {
        return ChatRoom::firstOrCreate(
            ['user_id' => $userId],
            ['status' => 'pending']
        );
    }

    public function getRooms(array $filters = [])
    {
        return ChatRoom::with(['user', 'latestMessage'])
                       ->when(!empty($filters['status']), fn($q) => $q->where('status', $filters['status']))
                       ->orderByDesc('last_message_at')
                       ->paginate(20);
    }

    public function getMessages(ChatRoom $room, int $perPage = 50)
    {
        return $room->messages()
                    ->with('sender:id,name,avatar')
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    public function sendMessage(ChatRoom $room, int $senderId, array $data): ChatMessage
    {
        $message = ChatMessage::create([
            'chat_room_id'    => $room->id,
            'sender_id'       => $senderId,
            'message'         => $data['message'] ?? null,
            'attachment'      => $data['attachment'] ?? null,
            'attachment_type' => $data['attachment_type'] ?? 'none',
            'is_read'         => false,
        ]);

        $room->update(['last_message_at' => now(), 'status' => 'open']);

        $message->load('sender:id,name,avatar');

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    public function markAsRead(ChatRoom $room, int $userId): void
    {
        $room->messages()
             ->where('sender_id', '!=', $userId)
             ->where('is_read', false)
             ->update(['is_read' => true]);
    }

    public function closeRoom(ChatRoom $room): void
    {
        $room->update(['status' => 'closed']);
    }
}
