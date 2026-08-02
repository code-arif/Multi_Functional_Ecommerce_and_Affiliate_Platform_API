<?php

namespace Modules\Shipping\Http\Controllers;

use Modules\Shipping\Models\ShippingRate;
use Modules\Shipping\Services\ShippingService;
use Modules\Shipping\Http\Resources\RateResource;
use Modules\Shipping\Http\Requests\StoreRateRequest;
use Modules\Shipping\Http\Requests\UpdateRateRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RateController
{
    use ApiResponse;

    public function __construct(private ShippingService $shippingService) {}

    public function index(Request $request): JsonResponse
    {
        $rates = ShippingRate::with(['courier', 'zone'])
            ->when($request->shipping_zone_uuid, fn($q, $v) => $q->where('shipping_zone_id', \Modules\Shipping\Models\ShippingZone::findByUuid($v)?->id))
            ->when($request->courier_uuid, fn($q, $v) => $q->where('courier_id', \Modules\Shipping\Models\Courier::findByUuid($v)?->id))
            ->when($request->method, fn($q, $v) => $q->byMethod($v))
            ->when($request->boolean('active_only'), fn($q) => $q->active())
            ->orderBy('base_rate')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse(RateResource::collection($rates));
    }

    public function show(ShippingRate $rate): JsonResponse
    {
        $rate->load('courier', 'zone');
        return $this->successResponse(new RateResource($rate));
    }

    public function store(StoreRateRequest $request): JsonResponse
    {
        $data = $this->mapUuids($request->validated());
        $rate = ShippingRate::create($data);
        return $this->createdResponse(new RateResource($rate->load('courier', 'zone')), 'Rate created.');
    }

    public function update(UpdateRateRequest $request, ShippingRate $rate): JsonResponse
    {
        $data = $this->mapUuids($request->validated());
        $rate->update($data);
        return $this->successResponse(new RateResource($rate->fresh()->load('courier', 'zone')), 'Rate updated.');
    }

    /**
     * Map public uuid references to internal foreign keys.
     */
    private function mapUuids(array $data): array
    {
        if (!empty($data['shipping_zone_uuid'])) {
            $data['shipping_zone_id'] = \Modules\Shipping\Models\ShippingZone::findByUuidOrFail($data['shipping_zone_uuid'])->id;
        }
        if (!empty($data['courier_uuid'])) {
            $data['courier_id'] = \Modules\Shipping\Models\Courier::findByUuidOrFail($data['courier_uuid'])->id;
        }
        unset($data['shipping_zone_uuid'], $data['courier_uuid']);
        return $data;
    }

    public function destroy(ShippingRate $rate): JsonResponse
    {
        $rate->delete();
        return $this->noContentResponse('Rate deleted.');
    }

    /**
     * Calculate shipping cost for a given set of parameters.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rate_uuid'  => 'required|exists:shipping_rates,uuid',
            'weight'     => 'nullable|numeric|min:0',
            'item_count' => 'nullable|integer|min:1',
        ]);

        $cost = $this->shippingService->calculateCost(
            \Modules\Shipping\Models\ShippingRate::findByUuidOrFail($validated['rate_uuid'])->id,
            $validated['weight'] ?? 0,
            $validated['item_count'] ?? 1
        );

        return $this->successResponse(['cost' => $cost]);
    }

    /**
     * Find applicable rates for a destination.
     */
    public function findRates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country'    => 'nullable|string|max:5',
            'state'      => 'nullable|string|max:100',
            'city'       => 'nullable|string|max:100',
            'weight'     => 'nullable|numeric|min:0',
            'item_count' => 'nullable|integer|min:1',
        ]);

        $rates = $this->shippingService->findRatesForDestination(
            $validated['country'] ?? null,
            $validated['state'] ?? null,
            $validated['city'] ?? null,
            $validated['weight'] ?? 0,
            $validated['item_count'] ?? 1
        );

        return $this->successResponse($rates);
    }
}
