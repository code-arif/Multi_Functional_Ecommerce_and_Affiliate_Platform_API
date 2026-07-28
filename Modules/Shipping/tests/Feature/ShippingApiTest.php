<?php

use Modules\Auth\Models\User;
use Modules\Shipping\Models\Courier;
use Modules\Shipping\Models\ShippingZone;
use Modules\Shipping\Models\ShippingRate;
use Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(\Tests\TestCase::class)->use(DatabaseTransactions::class);

// ─── Helpers ─────────────────────────────────────────────────────

function shippingCreateAdminUser(): User
{
    $user = User::create([
        'name'     => 'Shipping Admin',
        'email'    => 'shipping-admin-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status'   => 'active',
    ]);
    $user->assignRole('super-admin');
    return $user;
}

function shippingCreateCourier(): Courier
{
    return Courier::create([
        'name'               => 'Test Courier',
        'slug'               => 'test-courier-' . uniqid(),
        'display_name'       => 'Test Courier Express',
        'website'            => 'https://example.com/courier',
        'tracking_url_template' => 'https://example.com/track/{tracking_number}',
        'supported_services' => ['standard', 'express'],
        'is_active'          => true,
        'sort_order'         => 1,
    ]);
}

function shippingCreateZone(): ShippingZone
{
    return ShippingZone::create([
        'name'       => 'Domestic Zone',
        'slug'       => 'domestic-' . uniqid(),
        'countries'  => ['BD'],
        'states'     => ['Dhaka', 'Chittagong'],
        'is_active'  => true,
    ]);
}

function shippingCreateRate(ShippingZone $zone, Courier $courier): ShippingRate
{
    return ShippingRate::create([
        'shipping_zone_id'   => $zone->id,
        'courier_id'         => $courier->id,
        'name'               => 'Standard Delivery',
        'method'             => 'standard',
        'base_rate'          => 60.00,
        'rate_per_kg'        => 10.00,
        'rate_per_item'      => 5.00,
        'free_shipping_min'  => 1000.00,
        'estimated_days_min' => 3,
        'estimated_days_max' => 5,
        'is_active'          => true,
    ]);
}

// ═══════════════════════════════════════════════════════════════════
// COURIER ENDPOINTS
// ═══════════════════════════════════════════════════════════════════

describe('Courier CRUD', function () {

    it('lists couriers', function () {
        shippingCreateCourier();
        $response = $this->getJson('/api/v1/shipping/couriers');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });

    it('creates a courier', function () {
        $admin = shippingCreateAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/shipping/couriers', [
                'name'               => 'DHL Express',
                'display_name'       => 'DHL Express',
                'website'            => 'https://dhl.com',
                'tracking_url_template' => 'https://dhl.com/track/{tracking_number}',
                'supported_services' => ['express', 'freight'],
                'is_active'          => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('couriers', ['name' => 'DHL Express']);
    });

    it('updates a courier', function () {
        $admin = shippingCreateAdminUser();
        $courier = shippingCreateCourier();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/shipping/couriers/{$courier->id}", [
                'display_name' => 'Updated Name',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('couriers', ['display_name' => 'Updated Name']);
    });

    it('deletes a courier without rates', function () {
        $admin = shippingCreateAdminUser();
        $courier = shippingCreateCourier();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/shipping/couriers/{$courier->id}");

        $response->assertOk();
        $this->assertSoftDeleted('couriers', ['id' => $courier->id]);
    });

    it('denies courier creation for guests', function () {
        $response = $this->postJson('/api/v1/admin/shipping/couriers', [
            'name' => 'Unauthorized',
        ]);
        $response->assertUnauthorized();
    });
});

// ═══════════════════════════════════════════════════════════════════
// ZONE ENDPOINTS
// ═══════════════════════════════════════════════════════════════════

describe('Zone CRUD', function () {

    it('creates a shipping zone', function () {
        $admin = shippingCreateAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/shipping/zones', [
                'name'      => 'North America',
                'countries' => ['US', 'CA'],
                'is_active' => true,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('shipping_zones', ['name' => 'North America']);
    });

    it('lists zones', function () {
        $admin = shippingCreateAdminUser();
        shippingCreateZone();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/shipping/zones');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });

    it('updates a zone', function () {
        $admin = shippingCreateAdminUser();
        $zone = shippingCreateZone();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/shipping/zones/{$zone->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertOk();
    });

    it('deletes a zone without rates', function () {
        $admin = shippingCreateAdminUser();
        $zone = shippingCreateZone();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/shipping/zones/{$zone->id}");

        $response->assertOk();
        $this->assertSoftDeleted('shipping_zones', ['id' => $zone->id]);
    });
});

// ═══════════════════════════════════════════════════════════════════
// RATE ENDPOINTS
// ═══════════════════════════════════════════════════════════════════

describe('Rate CRUD', function () {

    it('creates a shipping rate', function () {
        $admin = shippingCreateAdminUser();
        $zone = shippingCreateZone();
        $courier = shippingCreateCourier();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/shipping/rates', [
                'shipping_zone_id'   => $zone->id,
                'courier_id'         => $courier->id,
                'name'               => 'Express Delivery',
                'method'             => 'express',
                'base_rate'          => 150.00,
                'rate_per_kg'        => 20.00,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'is_active'          => true,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('shipping_rates', ['name' => 'Express Delivery']);
    });

    it('finds rates for destination', function () {
        $zone = shippingCreateZone();
        $courier = shippingCreateCourier();
        shippingCreateRate($zone, $courier);

        $response = $this->getJson('/api/v1/shipping/rates/find?' . http_build_query([
            'country'    => 'BD',
            'state'      => 'Dhaka',
            'weight'     => 2,
            'item_count' => 1,
        ]));

        $response->assertOk();
    });

    it('calculates rate cost', function () {
        $admin = shippingCreateAdminUser();
        $zone = shippingCreateZone();
        $courier = shippingCreateCourier();
        $rate = shippingCreateRate($zone, $courier);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/shipping/rates/calculate', [
                'rate_id'    => $rate->id,
                'weight'     => 5,
                'item_count' => 2,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['cost']]);
    });
});

// ═══════════════════════════════════════════════════════════════════
// SHIPMENT ENDPOINTS
// ═══════════════════════════════════════════════════════════════════

describe('Shipment Management', function () {

    it('creates a shipment from an order', function () {
        $service = app(\Modules\Shipping\Services\ShippingService::class);
        $order = Order::create([
            'order_number'      => 'ORD-' . uniqid(),
            'user_id'           => shippingCreateAdminUser()->id,
            'subtotal'          => 500.00,
            'total'             => 560.00,
            'status'            => 'confirmed',
            'shipping_cost'     => 60.00,
            'shipping_method'   => 'standard',
            'shipping_address'  => json_encode([
                'name'    => 'John Doe',
                'phone'   => '01700000000',
                'address' => '123 Test St, Dhaka',
            ]),
        ]);

        $shipment = $service->createShipmentFromOrder($order);

        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'status'   => 'pending',
        ]);
    });

    it('updates shipment status', function () {
        $admin = shippingCreateAdminUser();
        $order = Order::create([
            'order_number'      => 'ORD-' . uniqid(),
            'user_id'           => $admin->id,
            'subtotal'          => 500.00,
            'total'             => 560.00,
            'status'            => 'confirmed',
            'shipping_cost'     => 60.00,
            'shipping_method'   => 'standard',
            'shipping_address'  => json_encode([
                'name'    => 'John Doe',
                'phone'   => '01700000000',
                'address' => '123 Test St, Dhaka',
            ]),
        ]);
        $shipment = app(\Modules\Shipping\Services\ShippingService::class)->createShipmentFromOrder($order);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/shipping/shipments/{$shipment->id}/status", [
                'status'      => 'in_transit',
                'description' => 'Package shipped via air.',
                'location'    => 'Dhaka Hub',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('tracking_histories', [
            'shipment_id' => $shipment->id,
            'status'      => 'in_transit',
        ]);
    });

    it('tracks shipment by tracking number', function () {
        $admin = shippingCreateAdminUser();
        $order = Order::create([
            'order_number'      => 'ORD-' . uniqid(),
            'user_id'           => $admin->id,
            'subtotal'          => 500.00,
            'total'             => 560.00,
            'status'            => 'confirmed',
            'shipping_cost'     => 60.00,
            'shipping_method'   => 'standard',
            'shipping_address'  => json_encode([
                'name'    => 'John Doe',
                'phone'   => '01700000000',
                'address' => '123 Test St, Dhaka',
            ]),
        ]);
        $service = app(\Modules\Shipping\Services\ShippingService::class);
        $shipment = $service->createShipmentFromOrder($order);
        $service->updateTracking($shipment->id, 'TRACK123', 'TRACK123');

        $response = $this->getJson('/api/v1/shipping/track/TRACK123');

        $response->assertOk()
            ->assertJsonPath('data.tracking_number', 'TRACK123');
    });

    it('returns 404 for unknown tracking number', function () {
        $response = $this->getJson('/api/v1/shipping/track/UNKNOWN123');
        $response->assertStatus(404);
    });

    it('denies access to admin shipment list for guests', function () {
        $response = $this->getJson('/api/v1/admin/shipping/shipments');
        $response->assertUnauthorized();
    });
});
