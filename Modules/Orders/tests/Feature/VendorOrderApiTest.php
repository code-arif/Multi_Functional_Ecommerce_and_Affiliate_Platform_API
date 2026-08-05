<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Models\Order;
use Modules\RBAC\Database\Seeders\RBACSeeder;
use Modules\Vendor\Models\Vendor;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RBACSeeder::class);
});

// ─── Helpers ─────────────────────────────────────────────────────

if (!function_exists('makeVendorUserForTest')) {
    function makeVendorUserForTest(): array
    {
        $user = makeCustomerUser();

        $vendor = Vendor::create([
            'user_id'         => $user->id,
            'shop_name'       => 'Test Shop ' . uniqid(),
            'slug'            => 'test-shop-' . uniqid(),
            'status'          => 'active',
            'commission_rate' => 10,
        ]);

        return [$user, $vendor];
    }
}

// ─── Listing & scoping ───────────────────────────────────────────

it('vendor can only see orders from their shop', function () {
    [$user, $vendor] = makeVendorUserForTest();

    Order::factory()->create(['vendor_id' => $vendor->id]);
    Order::factory()->create(['vendor_id' => 99999]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/vendor/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.vendor_id', $vendor->id);
});

it('vendor cannot view another shops order', function () {
    [$user] = makeVendorUserForTest();

    $otherOrder = Order::factory()->create(['vendor_id' => 99999]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/vendor/orders/' . $otherOrder->uuid)
        ->assertStatus(404);
});

it('vendor can view their order detail with customer info', function () {
    [$user, $vendor] = makeVendorUserForTest();

    $order = Order::factory()->create(['vendor_id' => $vendor->id, 'status' => 'confirmed']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/vendor/orders/' . $order->uuid)
        ->assertOk()
        ->assertJsonPath('data.vendor.id', $vendor->id);
});

// ─── Status management ───────────────────────────────────────────

it('vendor can move order through allowed transitions', function () {
    [$user, $vendor] = makeVendorUserForTest();

    $order = Order::factory()->create([
        'vendor_id' => $vendor->id,
        'status'    => 'confirmed',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/vendor/orders/' . $order->uuid . '/status', [
            'status' => 'processing',
            'note'   => 'Preparing shipment',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'processing');

    expect($order->statusHistories()->count())->toBe(1);
});

it('vendor cannot skip transitions', function () {
    [$user, $vendor] = makeVendorUserForTest();

    $order = Order::factory()->create([
        'vendor_id' => $vendor->id,
        'status'    => 'processing',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/vendor/orders/' . $order->uuid . '/status', [
            'status' => 'delivered',
        ])
        ->assertStatus(422);
});

it('vendor cannot update another shops order status', function () {
    [$user] = makeVendorUserForTest();

    $otherOrder = Order::factory()->create(['vendor_id' => 99999, 'status' => 'confirmed']);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/vendor/orders/' . $otherOrder->uuid . '/status', [
            'status' => 'processing',
        ])
        ->assertStatus(404);
});

// ─── Tracking & invoices ─────────────────────────────────────────

it('vendor can update tracking information', function () {
    [$user, $vendor] = makeVendorUserForTest();

    $order = Order::factory()->create([
        'vendor_id' => $vendor->id,
        'status'    => 'shipped',
    ]);

    $this->actingAs($user, 'sanctum')
        ->patchJson('/api/v1/vendor/orders/' . $order->uuid . '/tracking', [
            'tracking_number'  => 'BD123456789',
            'shipping_carrier' => 'Pathao',
        ])
        ->assertOk()
        ->assertJsonPath('data.tracking_number', 'BD123456789');
});

it('vendor can view their shop invoice', function () {
    [$user, $vendor] = makeVendorUserForTest();

    $order = Order::factory()->create(['vendor_id' => $vendor->id]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/vendor/orders/' . $order->uuid . '/invoice')
        ->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number);
});

// ─── Stats ───────────────────────────────────────────────────────

it('vendor can view their shop stats', function () {
    [$user, $vendor] = makeVendorUserForTest();

    Order::factory()->count(3)->create([
        'vendor_id' => $vendor->id,
        'status'    => 'delivered',
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/vendor/orders/stats')
        ->assertOk()
        ->assertJsonPath('data.total_orders', 3)
        ->assertJsonStructure(['data' => ['status_counts', 'month_revenue', 'total_revenue']]);
});

// ─── Authorization ───────────────────────────────────────────────

it('non vendor user is forbidden', function () {
    $customer = makeCustomerUser();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/vendor/orders')
        ->assertStatus(403);
});
