<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\ActivityLog;
use Modules\Orders\Models\CancelRequest;
use Modules\Orders\Models\Order;
use Modules\RBAC\Database\Seeders\RBACSeeder;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RBACSeeder::class);
});

// ─── Listing & viewing ───────────────────────────────────────────

it('admin can list all orders', function () {
    Order::factory()->count(3)->create();

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->getJson('/api/v1/admin/orders')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('admin can filter orders by status', function () {
    Order::factory()->create(['status' => 'pending']);
    Order::factory()->create(['status' => 'delivered']);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->getJson('/api/v1/admin/orders?status=delivered')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'delivered');
});

it('admin can view order detail', function () {
    $order = Order::factory()->create();

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->getJson('/api/v1/admin/orders/' . $order->uuid)
        ->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number);
});

it('admin can view platform order stats', function () {
    Order::factory()->count(2)->create(['status' => 'delivered']);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->getJson('/api/v1/admin/orders/stats')
        ->assertOk()
        ->assertJsonPath('data.total_orders', 2)
        ->assertJsonStructure(['data' => ['status_counts', 'total_revenue', 'month_revenue']]);
});

// ─── Status management ───────────────────────────────────────────

it('admin can update order status with history and audit', function () {
    $order = Order::factory()->create(['status' => 'confirmed']);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/orders/' . $order->uuid . '/status', [
            'status' => 'processing',
            'note'   => 'Packing items',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'processing');

    $this->assertDatabaseHas('order_status_histories', [
        'order_id'   => $order->id,
        'old_status' => 'confirmed',
        'new_status' => 'processing',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'module' => 'orders',
        'action' => 'status_updated',
    ]);
});

it('invalid status transition is rejected', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/orders/' . $order->uuid . '/status', [
            'status' => 'delivered',
        ])
        ->assertStatus(422);
});

it('cod order becomes paid when delivered', function () {
    $order = Order::factory()->create([
        'status'         => 'shipped',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
    ]);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/orders/' . $order->uuid . '/status', [
            'status' => 'delivered',
        ])
        ->assertOk();

    expect($order->fresh()->payment_status)->toBe('paid');
});

it('cancelling an order records cancel reason', function () {
    $order = Order::factory()->create(['status' => 'confirmed']);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/orders/' . $order->uuid . '/status', [
            'status' => 'cancelled',
            'note'   => 'Customer requested via support.',
        ])
        ->assertOk();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($order->fresh()->cancel_reason)->toBe('Customer requested via support.');
});

// ─── Notes & refunds ─────────────────────────────────────────────

it('admin can update admin note', function () {
    $order = Order::factory()->create();

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/orders/' . $order->uuid . '/note', [
            'note' => 'Customer called about delivery.',
        ])
        ->assertOk();

    expect($order->fresh()->admin_note)->toBe('Customer called about delivery.');
});

it('admin can refund a delivered order', function () {
    $order = Order::factory()->create([
        'status'         => 'delivered',
        'payment_method' => 'cod',
        'payment_status' => 'paid',
    ]);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->postJson('/api/v1/admin/orders/' . $order->uuid . '/refund', [
            'note' => 'Refund for damaged item.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'refunded');
});

// ─── Cancellation request review ─────────────────────────────────

it('admin can review and approve cancel request', function () {
    $customer = makeCustomerUser();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'pending']);

    $cancelRequest = CancelRequest::create([
        'order_id' => $order->id,
        'user_id'  => $customer->id,
        'reason'   => 'Changed my mind.',
        'status'   => 'pending',
    ]);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/cancel-requests/' . $cancelRequest->uuid, [
            'status'         => 'approved',
            'admin_response' => 'Approved.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    expect($order->fresh()->status)->toBe('cancelled');
});

it('admin can reject cancel request without cancelling order', function () {
    $customer = makeCustomerUser();
    $order = Order::factory()->create(['user_id' => $customer->id, 'status' => 'processing']);

    $cancelRequest = CancelRequest::create([
        'order_id' => $order->id,
        'user_id'  => $customer->id,
        'reason'   => 'Changed my mind.',
        'status'   => 'pending',
    ]);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/cancel-requests/' . $cancelRequest->uuid, [
            'status' => 'rejected',
        ])
        ->assertOk();

    expect($order->fresh()->status)->toBe('processing');
});

// ─── Audit trail ─────────────────────────────────────────────────

it('super admin can view order audit trail', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->patchJson('/api/v1/admin/orders/' . $order->uuid . '/status', ['status' => 'confirmed'])
        ->assertOk();

    $this->actingAs(makeAdminUser(), 'sanctum')
        ->getJson('/api/v1/admin/orders/audit')
        ->assertOk()
        ->assertJsonCount(ActivityLog::byModule('orders')->count(), 'data');
});

// ─── Authorization ───────────────────────────────────────────────

it('non admin cannot access admin orders', function () {
    $customer = makeCustomerUser();

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/admin/orders')
        ->assertStatus(403);
});
