<?php

namespace Modules\Support\Http\Controllers;

use Modules\Support\Models\ChatRoom;
use Modules\Support\Services\ChatService;
use Modules\Support\Http\Resources\ChatRoomResource;
use Modules\Support\Http\Resources\ChatMessageResource;
use Modules\Support\Http\Requests\CreateChatRoomRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController
{
    use ApiResponse;

    public function __construct(private ChatService $chatService) {}

    // ─── Customer Endpoints ───────────────────────────────────

    public function myRoom(Request $request): JsonResponse
    {
        $room = $request->user()->chatRooms()->active()->with('messages.user')->first();

        if (!$room) {
            return $this->successResponse(null, 'No active chat room.');
        }

        return $this->successResponse(new ChatRoomResource($room));
    }

    public function createRoom(CreateChatRoomRequest $request): JsonResponse
    {
        $room = $this->chatService->createRoom($request->user(), [
            'order_id' => $request->order_id,
            'subject'  => $request->subject,
        ]);

        return $this->createdResponse(new ChatRoomResource($room), 'Chat room created.');
    }

    public function messages(ChatRoom $room, Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin() && $request->user()->id !== $room->user_id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $messages = $this->chatService->getMessages($room);

        return $this->successResponse(ChatMessageResource::collection($messages));
    }

    public function sendMessage(ChatRoom $room, Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin() && $request->user()->id !== $room->user_id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'type'    => 'nullable|string|max:20',
        ]);

        $message = $this->chatService->sendMessage(
            $room,
            $request->user(),
            $validated['message'],
            $validated['type'] ?? 'text'
        );

        return $this->createdResponse(new ChatMessageResource($message), 'Message sent.');
    }

    // ─── Admin Endpoints ──────────────────────────────────────

    public function adminRooms(Request $request): JsonResponse
    {
        $rooms = $this->chatService->getAdminRooms();
        return $this->successResponse(ChatRoomResource::collection($rooms));
    }

    public function closeRoom(ChatRoom $room, Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $this->chatService->closeRoom($room);
        return $this->noContentResponse('Room closed.');
    }
}
