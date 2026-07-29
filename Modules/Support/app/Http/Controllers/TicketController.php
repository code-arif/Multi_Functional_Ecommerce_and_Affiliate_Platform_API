<?php

namespace Modules\Support\Http\Controllers;

use Modules\Support\Models\Ticket;
use Modules\Support\Models\TicketMessage;
use Modules\Support\Http\Resources\TicketResource;
use Modules\Support\Http\Resources\TicketMessageResource;
use Modules\Support\Http\Requests\StoreTicketRequest;
use Modules\Support\Http\Requests\AddTicketMessageRequest;
use Modules\Support\Http\Requests\UpdateTicketStatusRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::with('user')
            ->when(!$request->user()->isAdmin(), fn($q) => $q->byUser($request->user()->id))
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->when($request->priority, fn($q) => $q->byPriority($request->priority))
            ->when($request->category, fn($q) => $q->byCategory($request->category))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(TicketResource::collection($tickets));
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id'       => $request->user()->id,
            'order_id'      => $request->order_id,
            'category'      => $request->category,
            'subject'       => $request->subject,
            'description'   => $request->description,
            'priority'      => $request->priority ?? 'medium',
            'status'        => 'open',
        ]);

        return $this->createdResponse(
            new TicketResource($ticket->load('user')),
            'Ticket created.'
        );
    }

    public function show(Ticket $ticket, Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin() && $request->user()->id !== $ticket->user_id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $ticket->load(['messages.user', 'user', 'assignedTo']);
        return $this->successResponse(new TicketResource($ticket));
    }

    public function addMessage(Ticket $ticket, AddTicketMessageRequest $request): JsonResponse
    {
        if (!$request->user()->isAdmin() && $request->user()->id !== $ticket->user_id) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        if ($ticket->isClosed()) {
            return $this->errorResponse('Cannot add messages to a closed ticket.', null, 400);
        }

        $message = $ticket->messages()->create([
            'user_id'        => $request->user()->id,
            'message'        => $request->message,
            'attachments'    => $request->attachments,
            'is_staff_reply' => $request->user()->isAdmin(),
        ]);

        // Reopen if customer replies to resolved ticket
        if ($ticket->isResolved() && !$request->user()->isAdmin()) {
            $ticket->update(['status' => 'open']);
        }

        // Auto set to in_progress when admin replies
        if ($ticket->status === 'open' && $request->user()->isAdmin()) {
            $ticket->update(['status' => 'in_progress']);
        }

        return $this->createdResponse(
            new TicketMessageResource($message->load('user')),
            'Message added.'
        );
    }

    public function updateStatus(Ticket $ticket, UpdateTicketStatusRequest $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return $this->errorResponse('Forbidden.', null, 403);
        }

        $data = ['status' => $request->status];

        if (in_array($request->status, ['resolved', 'closed'])) {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);

        return $this->successResponse(
            new TicketResource($ticket->fresh()->load('user')),
            "Ticket {$request->status}."
        );
    }
}
