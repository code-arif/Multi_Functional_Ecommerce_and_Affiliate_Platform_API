<?php

namespace Modules\Orders\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\Orders\Http\Requests\ReviewCancelRequestRequest;
use Modules\Orders\Http\Resources\CancelRequestResource;
use Modules\Orders\Models\CancelRequest;
use Modules\Orders\Services\CancelRequestService;

class CancelRequestController
{
    use ApiResponse;

    public function __construct(private CancelRequestService $cancelRequestService)
    {
    }

    /**
     * GET /api/v1/admin/cancel-requests
     */
    public function index(Request $request): JsonResponse
    {
        $requests = CancelRequest::with(['order.vendor', 'user'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(CancelRequestResource::collection($requests), 'Cancellation requests fetched.');
    }

    /**
     * GET /api/v1/admin/cancel-requests/{cancelRequest}
     */
    public function show(CancelRequest $cancelRequest): JsonResponse
    {
        $cancelRequest->load(['order.items', 'user']);

        return $this->successResponse(new CancelRequestResource($cancelRequest));
    }

    /**
     * PATCH /api/v1/admin/cancel-requests/{cancelRequest}
     */
    public function review(ReviewCancelRequestRequest $request, CancelRequest $cancelRequest): JsonResponse
    {
        try {
            $cancelRequest = $this->cancelRequestService->review(
                $cancelRequest,
                $request->validated('status'),
                $request->validated('admin_response'),
                $request->user()
            );

            return $this->successResponse(
                new CancelRequestResource($cancelRequest->load('order')),
                'Cancellation request reviewed.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }
}
