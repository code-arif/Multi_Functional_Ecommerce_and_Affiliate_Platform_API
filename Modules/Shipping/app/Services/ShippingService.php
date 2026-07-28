<?php

namespace Modules\Shipping\Services;

use Modules\Shipping\Models\Courier;
use Modules\Shipping\Models\ShippingZone;
use Modules\Shipping\Models\ShippingRate;
use Modules\Shipping\Models\Shipment;
use Modules\Shipping\Models\PickupRequest;
use Modules\Orders\Models\Order;
use Illuminate\Support\Collection;

class ShippingService
{
    // ─── Rate Matching ────────────────────────────────────────────

    /**
     * Find the best applicable shipping rates for a given destination.
     */
    public function findRatesForDestination(
        ?string $country = null,
        ?string $state = null,
        ?string $city = null,
        ?float $weight = 0,
        ?int $itemCount = 1
    ): Collection {
        $zones = ShippingZone::active()->get();
        $applicableZones = $zones->filter(fn(ShippingZone $zone) =>
            $zone->coversLocation($country, $state, $city)
        );

        $zoneIds = $applicableZones->pluck('id');

        if ($zoneIds->isEmpty()) {
            $zoneIds = ShippingZone::active()
                ->whereNull('countries')
                ->orWhere('countries', '[]')
                ->pluck('id');
        }

        return ShippingRate::active()
            ->with(['courier', 'zone'])
            ->whereIn('shipping_zone_id', $zoneIds)
            ->get()
            ->map(function (ShippingRate $rate) use ($weight, $itemCount) {
                $cost = $rate->calculateCost($weight, $itemCount);
                return [
                    'id'              => $rate->id,
                    'name'            => $rate->name,
                    'method'          => $rate->method,
                    'cost'            => $cost,
                    'base_rate'       => (float) $rate->base_rate,
                    'estimated_days'  => $rate->getEstimatedDeliveryText(),
                    'courier'         => [
                        'id'   => $rate->courier->id,
                        'name' => $rate->courier->name,
                        'slug' => $rate->courier->slug,
                    ],
                ];
            })
            ->sortBy('cost')
            ->values();
    }

    /**
     * Calculate shipping cost for a given rate, weight, and item count.
     */
    public function calculateCost(int $rateId, float $weight = 0, int $itemCount = 1): float
    {
        $rate = ShippingRate::findOrFail($rateId);
        return $rate->calculateCost($weight, $itemCount);
    }

    // ─── Shipment Lifecycle ──────────────────────────────────────

    /**
     * Create a shipment from an order.
     *
     * Parses recipient info from the order's shipping_address JSON field
     * (the Orders module stores shipping details as a JSON text column).
     */
    public function createShipmentFromOrder(Order $order, array $data = []): Shipment
    {
        // Parse shipping address from JSON field (Orders module stores as JSON text)
        $shippingAddress = is_string($order->shipping_address)
            ? json_decode($order->shipping_address, true) ?: []
            : ($order->shipping_address ?? []);

        $recipientName    = $data['recipient_name'] ?? ($shippingAddress['name'] ?? '');
        $recipientPhone   = $data['recipient_phone'] ?? ($shippingAddress['phone'] ?? '');
        $recipientAddress = $data['recipient_address'] ?? ($shippingAddress['address'] ?? $order->shipping_address ?? '');

        return Shipment::create([
            'order_id'         => $order->id,
            'vendor_id'        => $data['vendor_id'] ?? null,
            'courier_id'       => $data['courier_id'] ?? null,
            'shipping_rate_id' => $data['shipping_rate_id'] ?? null,
            'tracking_number'  => $data['tracking_number'] ?? null,
            'status'           => Shipment::STATUS_PENDING,
            'method'           => $data['method'] ?? ($order->shipping_method ?? 'standard'),
            'weight'           => $data['weight'] ?? null,
            'shipping_cost'    => $data['shipping_cost'] ?? $order->shipping_cost ?? 0,
            'recipient_name'   => $recipientName,
            'recipient_phone'  => $recipientPhone,
            'recipient_address' => $recipientAddress,
        ]);
    }

    /**
     * Assign a courier to a shipment.
     */
    public function assignCourier(int $shipmentId, int $courierId, ?string $trackingNumber = null): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);
        $shipment->update([
            'courier_id'      => $courierId,
            'tracking_number' => $trackingNumber,
            'status'          => Shipment::STATUS_PROCESSING,
        ]);
        $shipment->addTrackingEntry(Shipment::STATUS_PROCESSING, 'Assigned to courier.');
        return $shipment->fresh()->load('courier', 'trackingHistories');
    }

    /**
     * Update shipment status with tracking info.
     */
    public function updateStatus(int $shipmentId, string $status, ?string $description = null, ?string $location = null): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);

        $updateData = ['status' => $status];
        if ($status === Shipment::STATUS_IN_TRANSIT && !$shipment->shipped_at) {
            $updateData['shipped_at'] = now();
        }
        if ($status === Shipment::STATUS_DELIVERED) {
            $updateData['delivered_at'] = now();
        }
        $shipment->update($updateData);

        $description = $description ?? $this->getDefaultStatusDescription($status);
        $shipment->addTrackingEntry($status, $description, $location);

        return $shipment->fresh()->load('trackingHistories');
    }

    /**
     * Update tracking number for a shipment.
     */
    public function updateTracking(int $shipmentId, string $trackingNumber, ?string $carrierCode = null): Shipment
    {
        $shipment = Shipment::findOrFail($shipmentId);
        $shipment->update([
            'tracking_number'       => $trackingNumber,
            'carrier_tracking_code' => $carrierCode ?? $trackingNumber,
        ]);
        return $shipment->fresh();
    }

    /**
     * Get full tracking history for a shipment.
     */
    public function getTrackingHistory(int $shipmentId): Collection
    {
        return Shipment::findOrFail($shipmentId)
            ->trackingHistories()
            ->latestFirst()
            ->get();
    }

    /**
     * Get shipment by tracking number (for guest tracking).
     */
    public function findByTrackingNumber(string $trackingNumber): ?Shipment
    {
        return Shipment::where('tracking_number', $trackingNumber)
            ->orWhere('carrier_tracking_code', $trackingNumber)
            ->with(['trackingHistories' => fn($q) => $q->latestFirst(), 'courier'])
            ->first();
    }

    // ─── Pickup Requests ─────────────────────────────────────────

    /**
     * Create a pickup request.
     */
    public function createPickupRequest(int $vendorId, array $data): PickupRequest
    {
        $pickup = PickupRequest::create([
            'vendor_id'       => $vendorId,
            'courier_id'      => $data['courier_id'],
            'status'          => PickupRequest::STATUS_PENDING,
            'pickup_date'     => $data['pickup_date'],
            'pickup_time_from' => $data['pickup_time_from'],
            'pickup_time_to'  => $data['pickup_time_to'],
            'address'         => $data['address'],
            'contact_name'    => $data['contact_name'],
            'contact_phone'   => $data['contact_phone'],
            'notes'           => $data['notes'] ?? null,
            'parcels'         => $data['parcels'] ?? null,
        ]);

        return $pickup->fresh()->load('courier');
    }

    /**
     * Get default description for a status update.
     */
    private function getDefaultStatusDescription(string $status): string
    {
        return match ($status) {
            Shipment::STATUS_PENDING       => 'Shipment created and pending processing.',
            Shipment::STATUS_PROCESSING    => 'Shipment is being prepared.',
            Shipment::STATUS_PICKED_UP     => 'Package has been picked up by the courier.',
            Shipment::STATUS_IN_TRANSIT    => 'Shipment is in transit to destination.',
            Shipment::STATUS_OUT_FOR_DELIVERY => 'Package is out for delivery.',
            Shipment::STATUS_DELIVERED     => 'Package has been delivered successfully.',
            Shipment::STATUS_FAILED        => 'Delivery attempt failed.',
            Shipment::STATUS_RETURNED      => 'Package has been returned to sender.',
            Shipment::STATUS_CANCELLED     => 'Shipment has been cancelled.',
            default                       => "Status updated to: {$status}",
        };
    }
}
