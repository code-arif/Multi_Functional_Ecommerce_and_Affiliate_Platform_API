<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Orders\Models\CancelRequest;
use Modules\Orders\Models\Order;
use Modules\Product\Models\Product;
use Modules\RBAC\Database\Seeders\RBACSeeder;

uses(Tests\TestCase::class)->use(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RBACSeeder::class);
});

// ─── Helpers ─────────────────────────────────────────────────────

if (!function_exists('makeOrderForTest')) {
    function makeOrderForTest(User $user, array $attributes = []): Order
    {
        $product = Product::create([
            'name'               => 'Order Test Product',
            'slug'               => 'order-test-' . uniqid(),
            'sku'                => 'SKU-' . uniqid(),
            'type'               => 'simple',
            'price'              => 100,
            'sale_price'         => null,
            'stock_quantity'     => 50,
            'low_stock_threshold'=> 5,
            'manage_stock'       => true,
            'stock_status'       => 'in_stock',
            'status'             => 'active',
        ]);

        $order = Order::factory()->create(array_merge([
            'user_id' => $user->id,
            'status'  => 'pending',
        ], $attributes));

        $order->items()->create([
            'product_id'    => $product->id,
            'product_name'  => $product->name,
            'product_sku'   => $product->sku,
            'unit_price'    => 100,
            'quantity'      => 2,
            'subtotal'      => 200,
        ]);

        return $order;
    }
}

// ─── Customer order listing & viewing ────────────────────────────

it('customer can list only their own orders', function () {
    $customer = makeCustomerUser();
    $other    = makeCustomerUser();

    makeOrderForTest($customer);
    makeOrderForTest($other);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $customer->id);
});

it('customer cannot view another customers order', function () {
    $customer = makeCustomerUser();
    $other    = makeCustomerUser();

    $order = makeOrderForTest($other);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/orders/' . $order->order_number)
        ->assertStatus(404);
});

it('customer can view own order detail with items', function () {
    $customer = makeCustomerUser();
    $order = makeOrderForTest($customer);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/orders/' . $order->order_number)
        ->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number)
        ->assertJsonPath('data.items.0.product_name', 'Order Test Product')
        ->assertJsonStructure(['data' => ['items', 'status_histories', 'timestamps']]);
});

it('customer can get order summary', function () {
    $customer = makeCustomerUser();

    makeOrderForTest($customer);
    makeOrderForTest($customer, ['status' => 'delivered']);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/orders/summary')
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

it('customer can download order invoice', function () {
    $customer = makeCustomerUser();
    $order = makeOrderForTest($customer);

    $this->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/orders/' . $order->order_number . '/invoice')
        ->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number)
        ->assertJsonPath('data.total_amount', (float) $order->total_amount);
});

// ─── Guest tracking ──────────────────────────────────────────────

it('guest can track order with public token', function () {
    $customer = makeCustomerUser();
    $order = makeOrderForTest($customer);

    $this->getJson('/api/v1/orders/track/' . $order->tracking_token)
        ->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number);
});

it('invalid tracking token returns 404', function () {
    $this->getJson('/api/v1/orders/track/invalid-token')
        ->assertStatus(404);
});

// ─── Cancellation requests ───────────────────────────────────────

it('customer can request cancellation', function () {
    $customer = makeCustomerUser();
    $order = makeOrderForTest($customer);

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/orders/' . $order->order_number . '/cancel-request', [
            'reason' => 'I changed my mind.',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('cancel_requests', [
        'order_id' => $order->id,
        'status'   => 'pending',
    ]);
});

it('customer cannot request cancellation twice while pending', function () {
    $customer = makeCustomerUser();
    $order = makeOrderForTest($customer);

    CancelRequest::create([
        'order_id' => $order->id,
        'user_id'  => $customer->id,
        'reason'   => 'First request',
        'status'   => 'pending',
    ]);

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/orders/' . $order->order_number . '/cancel-request', [
            'reason' => 'Second request',
        ])
        ->assertStatus(422);
});

it('customer cannot request cancellation for a shipped order', function () {
    $customer = makeCustomerUser();
    $order = makeOrderForTest($customer, ['status' => 'shipped']);

    $this->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/orders/' . $order->order_number . '/cancel-request', [
            'reason' => 'Too late.',
        ])
        ->assertStatus(422);
});

// ─── Authentication ──────────────────────────────────────────────

it('unauthenticated user cannot access orders', function () {
    $this->getJson('/api/v1/orders')->assertStatus(401);
});
