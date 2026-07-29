<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\AdminPanel\Models\Dispute;
use Modules\AdminPanel\Models\DisputeMessage;
use Modules\AdminPanel\Http\Resources\DisputeResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $disputes = Dispute::with(['order', 'customer', 'vendor'])
            ->when($request->status, fn($q) => $q->byStatus($request->status))
            ->when($request->search, fn($q) => $q->where('subject', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(DisputeResource::collection($disputes));
    }

    public function show(Dispute $dispute): JsonResponse
    {
        $dispute->load(['order.items', 'customer', 'vendor', 'messages.user', 'resolvedBy']);
        return $this->successResponse(new DisputeResource($dispute));
    }

    public function updateStatus(Request $request, Dispute $dispute): JsonResponse
    {
        $validated = $request->validate([
            'status'           => 'required|in:open,under_review,resolved,closed',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        $data = [
            'status' => $validated['status'],
        ];

        if (in_array($validated['status'], ['resolved', 'closed'])) {
            $data['resolved_by'] = auth()->id();
            $data['resolved_at'] = now();
        }

        if (isset($validated['resolution_notes'])) {
            $data['resolution_notes'] = $validated['resolution_notes'];
        }

        $dispute->update($data);

        return $this->successResponse(
            new DisputeResource($dispute->fresh()->load(['order', 'customer', 'vendor', 'resolvedBy'])),
            "Dispute {$validated['status']}."
        );
    }

    public function addMessage(Request $request, Dispute $dispute): JsonResponse
    {
        $validated = $request->validate([
            'message'     => 'required|string|max:5000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string|max:500',
        ]);

        $message = $dispute->messages()->create([
            'user_id'     => auth()->id(),
            'message'     => $validated['message'],
            'attachments' => $validated['attachments'] ?? null,
        ]);

        // Auto set to under_review when admin responds to an open dispute
        if ($dispute->isOpen()) {
            $dispute->update(['status' => 'under_review']);
        }

        return $this->createdResponse(
            new DisputeMessageResource($message->load('user')),
            'Message added.'
        );
    }
}
