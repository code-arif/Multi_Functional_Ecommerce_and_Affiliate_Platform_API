<?php

namespace Modules\Support\Http\Controllers;

use Modules\Support\Services\ChatService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController
{
    use ApiResponse;

    public function __construct(private ChatService $chatService) {}

    public function myRoom(Request $request): JsonResponse
    {
        $room = $request->user()->chatRooms()->active()->first();

        if (!$room) {
            return $this->successResponse(null, 'No active chat room.');
        }

        return $this->successResponse($room->load('messages.user'));
    }

    public function messages(int $room, Request $request): JsonResponse
    {
        $roomModel = \Modules\Support\Models\ChatRoom::findOrFail($room);
        $messages = $this->chatService->getMessages($roomModel);

        return $this->successResponse($messages);
    }

    public function sendMessage(int $room, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'type'    => 'nullable|string|max:20',
        ]);

        $roomModel = \Modules\Support\Models\ChatRoom::findOrFail($room);
        $message = $this->chatService->sendMessage(
            $roomModel,
            $request->user(),
            $validated['message'],
            $validated['type'] ?? 'text'
        );

        return $this->createdResponse($message, 'Message sent.');
    }

    public function adminRooms(Request $request): JsonResponse
    {
        $rooms = $this->chatService->getAdminRooms();
        return $this->successResponse($rooms);
    }

    public function closeRoom(int $room, Request $request): JsonResponse
    {
        $roomModel = \Modules\Support\Models\ChatRoom::findOrFail($room);
        $this->chatService->closeRoom($roomModel);

        return $this->noContentResponse('Room closed.');
    }
}
