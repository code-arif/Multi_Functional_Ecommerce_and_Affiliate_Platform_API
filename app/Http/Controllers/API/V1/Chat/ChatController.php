<?php

namespace App\Http\Controllers\API\V1\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    /**
     * GET /api/v1/chat/room — Customer gets or creates their room
     */
    public function myRoom(Request $request): JsonResponse
    {
        $room = $this->chatService->getOrCreateRoom($request->user()->id);
        return $this->successResponse([
            'room_id' => $room->id,
            'status'  => $room->status,
        ]);
    }

    /**
     * GET /api/v1/chat/room/{room}/messages
     */
    public function messages(Request $request, ChatRoom $room): JsonResponse
    {
        $this->authorizeRoom($request, $room);
        $messages = $this->chatService->getMessages($room);
        $this->chatService->markAsRead($room, $request->user()->id);
        return $this->paginatedResponse($messages);
    }

    /**
     * POST /api/v1/chat/room/{room}/messages
     */
    public function sendMessage(Request $request, ChatRoom $room): JsonResponse
    {
        $request->validate([
            'message'    => 'nullable|string|max:2000|required_without:attachment',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $this->authorizeRoom($request, $room);

        $data = ['message' => $request->message];
        if ($request->hasFile('attachment')) {
            $data['attachment']      = $request->file('attachment')->store('chat', 'public');
            $data['attachment_type'] = str_contains($request->file('attachment')->getMimeType(), 'image') ? 'image' : 'file';
        }

        $message = $this->chatService->sendMessage($room, $request->user()->id, $data);

        return $this->createdResponse([
            'id'              => $message->id,
            'message'         => $message->message,
            'attachment_url'  => $message->attachment_url,
            'attachment_type' => $message->attachment_type,
            'sender'          => [
                'id'   => $message->sender->id,
                'name' => $message->sender->name,
            ],
            'created_at' => $message->created_at->toDateTimeString(),
        ]);
    }

    // Admin: list all rooms
    public function adminRooms(Request $request): JsonResponse
    {
        $rooms = $this->chatService->getRooms($request->only('status'));
        return $this->paginatedResponse($rooms);
    }

    // Admin: close room
    public function closeRoom(ChatRoom $room): JsonResponse
    {
        $this->chatService->closeRoom($room);
        return $this->successResponse(null, 'Chat room closed.');
    }

    private function authorizeRoom(Request $request, ChatRoom $room): void
    {
        $user = $request->user();
        if (!$user->isAdmin() && $room->user_id !== $user->id) {
            abort(403, 'Access denied.');
        }
    }
}
