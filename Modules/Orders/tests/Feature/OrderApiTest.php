<?php

namespace Modules\Orders\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $vendorUser;
    private Vendor $vendor;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Order Test User',
            'email'  => 'orderuser@example.com',
            'status' => 'active',
        ]);

        $this->vendorUser = User::factory()->create([
            'name'   => 'Vendor User',
            'email'  => 'vendor-orders@example.com',
            'status' => 'active',
        ]);

        $this->vendor = Vendor::factory()->create([
            'user_id'   => $this->vendorUser->id,
            'shop_name' => 'Order Vendor Shop',
            'slug'      => 'order-vendor',
            'status'    => 'active',
        ]);

        $this->order = Order::create([
            'user_id'       => $this->user->id,
            'vendor_id'     => $this->vendor->id,
            'order_number'  => 'ORD-TEST-001',
            'subtotal'      => 200.00,
            'shipping_cost' => 60.00,
            'total'         => 260.00,
            'status'        => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'tracking_token' => 'tok-test-123',
        ]);

        $this->order->items()->create([
            'product_id'   => Product::factory()->create(['slug' => 'order-test-prod', 'status' => 'active'])->id,
            'product_name' => 'Test Product',
            'quantity'     => 2,
            'unit_price'   => 100.00,
            'total_price'  => 200.00,
        ]);
    }

    /** @test */
    public function user_can_view_own_orders(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/orders');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function user_can_view_own_order_by_number(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->order_number}");

        $response->assertOk()
            ->assertJsonPath('data.order_number', 'ORD-TEST-001');
    }

    /** @test */
    public function user_cannot_view_another_users_order(): void
    {
        $otherUser = User::factory()->create(['email' => 'other@example.com', 'status' => 'active']);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/orders/{$this->order->order_number}");

        $response->assertStatus(404);
    }

    /** @test */
    public function user_can_cancel_pending_order(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->order_number}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    /** @test */
    public function user_can_request_cancellation(): void
    {
        // Set to shipped so direct cancel isn't possible
        $this->order->update(['status' => 'shipped']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->order_number}/cancel-request", [
                'reason' => 'Item arrived damaged',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function user_cannot_cancel_shipped_order_directly(): void
    {
        $this->order->update(['status' => 'shipped']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->order_number}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function guest_can_track_order_by_token(): void
    {
        $response = $this->getJson("/api/v1/orders/track/{$this->order->tracking_token}");

        $response->assertOk()
            ->assertJsonPath('data.order_number', 'ORD-TEST-001');
    }

    /** @test */
    public function vendor_can_view_their_orders(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->getJson('/api/v1/vendor/orders');

        $response->assertOk();
    }

    /** @test */
    public function vendor_can_update_order_status(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->patchJson("/api/v1/vendor/orders/{$this->order->order_number}/status", [
                'status' => 'confirmed',
            ]);

        $response->assertOk();
    }

    /** @test */
    public function status_transition_validation_works(): void
    {
        $this->order->update(['status' => 'delivered']);

        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->patchJson("/api/v1/vendor/orders/{$this->order->order_number}/status", [
                'status' => 'cancelled', // Can't go from delivered to cancelled
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/v1/orders');
        $response->assertStatus(401);
    }
}
