<?php

namespace Modules\Shipping\Http\Controllers;

use Modules\Shipping\Models\PickupRequest;
use Modules\Shipping\Services\ShippingService;
use Modules\Shipping\Http\Resources\PickupResource;
use Modules\Shipping\Http\Requests\StorePickupRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupController
{
    use ApiResponse;

    public function __construct(private ShippingService $shippingService) {}

    /**
     * List pickup requests for the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->forbiddenResponse('You are not a vendor.');
        }

        $pickups = PickupRequest::with('courier')
            ->where('vendor_id', $vendor->id)
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(PickupResource::collection($pickups));
    }

    /**
     * Create a new pickup request.
     */
    public function store(StorePickupRequest $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor) {
            return $this->forbiddenResponse('You are not a vendor.');
        }

        $pickup = $this->shippingService->createPickupRequest($vendor->id, $request->validated());
        return $this->createdResponse(new PickupResource($pickup), 'Pickup request created.');
    }

    /**
     * Cancel a pickup request.
     */
    public function cancel(PickupRequest $pickup, Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;
        if (!$vendor || $pickup->vendor_id !== $vendor->id) {
            return $this->forbiddenResponse();
        }

        if ($pickup->status !== PickupRequest::STATUS_PENDING) {
            return $this->errorResponse('Only pending pickups can be cancelled.', null, 400);
        }

        $pickup->update([
            'status'       => PickupRequest::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $this->successResponse(new PickupResource($pickup->fresh()), 'Pickup cancelled.');
    }

    /**
     * Admin: list all pickup requests.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $pickups = PickupRequest::with(['vendor', 'courier'])
            ->when($request->status, fn($q, $v) => $q->where('status', $v))
            ->when($request->vendor_id, fn($q, $v) => $q->where('vendor_id', $v))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(PickupResource::collection($pickups));
    }

    /**
     * Admin: mark pickup as scheduled.
     */
    public function schedule(PickupRequest $pickup): JsonResponse
    {
        $pickup->update([
            'status'       => PickupRequest::STATUS_SCHEDULED,
            'scheduled_at' => now(),
        ]);
        return $this->successResponse(new PickupResource($pickup->fresh()), 'Pickup scheduled.');
    }

    /**
     * Admin: mark pickup as completed.
     */
    public function markPickedUp(PickupRequest $pickup): JsonResponse
    {
        $pickup->update([
            'status'       => PickupRequest::STATUS_PICKED_UP,
            'picked_up_at' => now(),
        ]);
        return $this->successResponse(new PickupResource($pickup->fresh()), 'Pickup marked as collected.');
    }
}
