<?php

namespace Modules\Support\Policies;

use App\Models\User;
use Modules\Support\Models\Ticket;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('support.tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('support.tickets.view') || $user->id === $ticket->user_id;
    }

    public function create(User $user): bool
    {
        return true; // Any authenticated user can create a ticket
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('support.tickets.manage');
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->hasPermissionTo('support.tickets.manage');
    }

    public function addMessage(User $user, Ticket $ticket): bool
    {
        // Ticket owner or admin can add messages to open tickets
        return $user->id === $ticket->user_id || $user->isAdmin() || $user->hasPermissionTo('support.tickets.manage');
    }
}
