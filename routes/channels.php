<?php

use App\Models\ChatRoom;
use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

// User's private notification channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Chat room channel — user or admin can join
Broadcast::channel('chat.room.{roomId}', function ($user, $roomId) {
    $room = ChatRoom::find($roomId);
    if (!$room) return false;
    return $user->isAdmin() || $room->user_id === $user->id;
});

// Admin broadcast channel
Broadcast::channel('admin', function ($user) {
    return $user->isAdmin();
});

// Order channel — user tracks their order
Broadcast::channel('orders.{orderNumber}', function ($user, $orderNumber) {
    return Order::where('order_number', $orderNumber)
                            ->where('user_id', $user->id)
                            ->exists();
});
