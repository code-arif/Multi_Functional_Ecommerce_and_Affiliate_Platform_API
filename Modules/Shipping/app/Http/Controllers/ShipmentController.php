<?php

namespace Modules\Shipping\Http\Controllers;

use Modules\Shipping\Models\Shipment;
use Modules\Shipping\Services\ShippingService;
use Modules\Shipping\Http\Resources\ShipmentResource;
use Modules\Shipping\Http\Resources\TrackingHistoryResource;
use Modules\Shipping\Http\Requests\UpdateShipmentStatusRequest;
use Modules\Shipping\Http\Requests\AssignCourierRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController
{
    use ApiResponse;

    public function __construct(private ShippingService $shippingService) {}

    public function index(Request $request): JsonResponse
    {
        $shipments = Shipment::with(['courier', 'order'])
            ->when($request->status, fn($q, $v) => $q->byStatus($v))
            ->when($request->order_id, fn($q, $v) => $q->where('order_id', $v))
            ->when($request->vendor_id, fn($q, $v) => $q->where('vendor_id', $v))
            ->when($request->tracking, fn($q, $v) =>
                $q->where('tracking_number', 'like', "%{$v}%")
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(ShipmentResource::collection($shipments));
    }

    /**
     * List shipments scoped to the authenticated vendor.
     * Used by the vendor/shipping/* routes.
     */
    public function vendorIndex(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->forbiddenResponse('You are not a vendor.');
        }

        $shipments = Shipment::with(['courier', 'order'])
            ->where('vendor_id', $vendor->id)
            ->when($request->status, fn($q, $v) => $q->byStatus($v))
            ->when($request->order_id, fn($q, $v) => $q->where('order_id', $v))
            ->when($request->tracking, fn($q, $v) =>
                $q->where('tracking_number', 'like', "%{$v}%")
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(ShipmentResource::collection($shipments));
    }

    public function vendorShow(Shipment $shipment, Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor || $shipment->vendor_id !== $vendor->id) {
            return $this->forbiddenResponse();
        }

        $shipment->load(['courier', 'order', 'trackingHistories' => fn($q) => $q->latestFirst()]);
        return $this->successResponse(new ShipmentResource($shipment));
    }

    public function vendorUpdateStatus(UpdateShipmentStatusRequest $request, Shipment $shipment): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor || $shipment->vendor_id !== $vendor->id) {
            return $this->forbiddenResponse();
        }

        $shipment = $this->shippingService->updateStatus(
            $shipment->id,
            $request->validated('status'),
            $request->validated('description'),
            $request->validated('location')
        );

        return $this->successResponse(new ShipmentResource($shipment), 'Shipment status updated.');
    }

    public function vendorTracking(Shipment $shipment, Request $request): JsonResponse
    {
        $vendor = $request->user()->vendor;

        if (!$vendor || $shipment->vendor_id !== $vendor->id) {
            return $this->forbiddenResponse();
        }

        $history = $this->shippingService->getTrackingHistory($shipment->id);
        return $this->successResponse(TrackingHistoryResource::collection($history));
    }

    public function show(Shipment $shipment): JsonResponse
    {
        $shipment->load(['courier', 'order', 'trackingHistories' => fn($q) => $q->latestFirst()]);
        return $this->successResponse(new ShipmentResource($shipment));
    }

    /**
     * Update shipment status with optional tracking entry.
     */
    public function updateStatus(UpdateShipmentStatusRequest $request, Shipment $shipment): JsonResponse
    {
        $shipment = $this->shippingService->updateStatus(
            $shipment->id,
            $request->validated('status'),
            $request->validated('description'),
            $request->validated('location')
        );

        return $this->successResponse(new ShipmentResource($shipment), 'Shipment status updated.');
    }

    /**
     * Assign a courier to a shipment.
     */
    public function assignCourier(AssignCourierRequest $request, Shipment $shipment): JsonResponse
    {
        $shipment = $this->shippingService->assignCourier(
            $shipment->id,
            $request->validated('courier_id'),
            $request->validated('tracking_number')
        );

        if ($request->filled('carrier_tracking_code')) {
            $this->shippingService->updateTracking(
                $shipment->id,
                $request->validated('tracking_number'),
                $request->validated('carrier_tracking_code')
            );
        }

        return $this->successResponse(new ShipmentResource($shipment->fresh()->load(['courier', 'trackingHistories'])), 'Courier assigned.');
    }

    /**
     * Get tracking history for a shipment.
     */
    public function tracking(Shipment $shipment): JsonResponse
    {
        $history = $this->shippingService->getTrackingHistory($shipment->id);
        return $this->successResponse(TrackingHistoryResource::collection($history));
    }

    /**
     * Track shipment by tracking number (public/no-auth).
     */
    public function trackByNumber(string $trackingNumber): JsonResponse
    {
        $shipment = $this->shippingService->findByTrackingNumber($trackingNumber);

        if (!$shipment) {
            return $this->errorResponse('Shipment not found with this tracking number.', null, 404);
        }

        return $this->successResponse(new ShipmentResource($shipment));
    }
}
