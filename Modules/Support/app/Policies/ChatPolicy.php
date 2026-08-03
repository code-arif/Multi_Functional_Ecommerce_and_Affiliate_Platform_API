<?php

namespace Modules\Support\Policies;

use App\Models\User;
use Modules\Support\Models\ChatRoom;

class ChatPolicy
{
    public function viewAny(User $user): bool { return $user->isAdmin() || $user->hasPermissionTo('support.chat.view'); }
    public function view(User $user, ChatRoom $room): bool { return $user->isAdmin() || $user->id === $room->user_id; }
    public function create(User $user): bool { return true; }
    public function sendMessage(User $user, ChatRoom $room): bool { return $user->isAdmin() || $user->id === $room->user_id; }
    public function close(User $user, ChatRoom $room): bool { return $user->isAdmin() || $user->hasPermissionTo('support.chat.manage'); }
}
