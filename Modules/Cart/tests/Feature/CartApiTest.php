<?php

namespace Modules\Cart\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\RecentView;
use Modules\Cart\Models\CompareList;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Test Customer',
            'email'  => 'customer-cart@example.com',
            'status' => 'active',
        ]);

        $this->product = Product::factory()->create([
            'name'           => 'Cart Test Product',
            'slug'           => 'cart-test-product',
            'sku'            => 'CART-001',
            'type'           => 'simple',
            'price'          => 100.00,
            'stock_quantity' => 50,
            'stock_status'   => 'in_stock',
            'status'         => 'active',
        ]);
    }

    // ─── Cart ─────────────────────────────────────────────────────

    /** @test */
    public function guest_can_create_cart_via_session(): void
    {
        $response = $this->withHeaders(['X-Session-ID' => 'test-session-123'])
            ->getJson('/api/v1/cart');

        $response->assertOk()
            ->assertJsonPath('data.is_empty', true);
    }

    /** @test */
    public function authenticated_user_can_view_cart(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/cart');

        $response->assertOk();
    }

    /** @test */
    public function user_can_add_item_to_cart(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 2,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.item_count', 2);
    }

    /** @test */
    public function user_cannot_exceed_stock_quantity(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 999, // Exceeds stock of 50
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function user_can_update_cart_item_quantity(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 1,
            ]);

        $cart = Cart::where('user_id', $this->user->id)->first();
        $item = $cart->items()->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/cart/items/{$item->id}", [
                'quantity' => 3,
            ]);

        $response->assertOk();
    }

    /** @test */
    public function user_can_remove_cart_item(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 1,
            ]);

        $cart = Cart::where('user_id', $this->user->id)->first();
        $item = $cart->items()->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/cart/items/{$item->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_clear_cart(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity'   => 1,
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/cart');

        $response->assertStatus(200);
    }

    // ─── Recently Viewed ──────────────────────────────────────────

    /** @test */
    public function guest_can_track_recently_viewed(): void
    {
        $response = $this->withHeaders(['X-Session-ID' => 'test-session-456'])
            ->postJson("/api/v1/recently-viewed/{$this->product->id}");

        $response->assertOk();

        $this->assertDatabaseHas('recent_views', [
            'product_id' => $this->product->id,
            'session_id' => 'test-session-456',
        ]);
    }

    /** @test */
    public function authenticated_user_can_view_recently_viewed(): void
    {
        RecentView::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/recently-viewed');

        $response->assertOk();
    }

    // ─── Compare List ─────────────────────────────────────────────

    /** @test */
    public function guest_can_add_to_compare_list(): void
    {
        $response = $this->withHeaders(['X-Session-ID' => 'cmp-session'])
            ->postJson("/api/v1/compare/{$this->product->id}");

        $response->assertOk();
    }

    /** @test */
    public function authenticated_user_can_view_compare_list(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/compare');

        $response->assertOk();
    }

    /** @test */
    public function user_can_remove_from_compare_list(): void
    {
        // Add first
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/compare/{$this->product->id}");

        // Then remove
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/compare/{$this->product->id}");

        $response->assertOk();
    }

    /** @test */
    public function user_can_clear_compare_list(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/compare/{$this->product->id}");

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/compare');

        $response->assertStatus(200);
    }

    // ─── Wishlist ─────────────────────────────────────────────────

    /** @test */
    public function authenticated_user_can_view_wishlist(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/wishlist');

        $response->assertOk();
    }

    /** @test */
    public function user_can_toggle_wishlist(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/wishlist', [
                'product_id' => $this->product->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.wishlisted', true);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_wishlist(): void
    {
        $response = $this->getJson('/api/v1/wishlist');
        $response->assertStatus(401);
    }
}
