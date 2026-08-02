<?php

namespace Modules\Shipping\Http\Controllers;

use Modules\Shipping\Models\ShippingZone;
use Modules\Shipping\Http\Resources\ZoneResource;
use Modules\Shipping\Http\Requests\StoreZoneRequest;
use Modules\Shipping\Http\Requests\UpdateZoneRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $zones = ShippingZone::withCount('rates')
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
            )
            ->when($request->boolean('active_only'), fn($q) => $q->active())
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse(ZoneResource::collection($zones));
    }

    public function show(ShippingZone $zone): JsonResponse
    {
        $zone->load('rates.courier');
        return $this->successResponse(new ZoneResource($zone));
    }

    public function store(StoreZoneRequest $request): JsonResponse
    {
        $zone = ShippingZone::create($request->validated());
        return $this->createdResponse(new ZoneResource($zone), 'Shipping zone created.');
    }

    public function update(UpdateZoneRequest $request, ShippingZone $zone): JsonResponse
    {
        $zone->update($request->validated());
        return $this->successResponse(new ZoneResource($zone->fresh()), 'Shipping zone updated.');
    }

    public function destroy(ShippingZone $zone): JsonResponse
    {
        if ($zone->rates()->count() > 0) {
            return $this->errorResponse('Cannot delete zone with active rates.', null, 400);
        }
        $zone->delete();
        return $this->noContentResponse('Shipping zone deleted.');
    }
}
