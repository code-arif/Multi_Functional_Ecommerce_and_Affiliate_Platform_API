<?php

namespace Modules\Checkout\Tests\Feature;

use App\Models\User;
use Modules\Product\Models\Product;
use Tests\TestCase;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Checkout User',
            'email'  => 'checkout@example.com',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name'               => 'Checkout Test Product',
            'slug'               => 'checkout-test',
            'sku'                => 'CHK-001',
            'type'               => 'simple',
            'price'              => 100.00,
            'sale_price'         => null,
            'stock_quantity'     => 50,
            'low_stock_threshold'=> 5,
            'manage_stock'       => true,
            'stock_status'       => 'in_stock',
            'status'             => 'active',
        ]);

        // Create cart directly (avoid auto-discovery issues with module-intersecting factories)
        $this->cart = Cart::create([
            'user_id' => $this->user->id,
        ]);

        $this->cart->items()->create([
            'product_id'  => $this->product->id,
            'quantity'    => 2,
            'unit_price'  => 100.00,
            'total_price' => 200.00,
        ]);
    }

    /** @test */
    public function authenticated_user_can_preview_order(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/checkout/preview');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['subtotal', 'grand_total', 'item_count', 'items_by_vendor'],
            ]);
    }

    /** @test */
    public function authenticated_user_can_process_checkout(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'shipping_address' => [
                    'address_line' => '123 Test St',
                    'city'         => 'Dhaka',
                    'country'      => 'Bangladesh',
                ],
                'payment_method' => 'cod',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function checkout_requires_shipping_address(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'payment_method' => 'cod',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function empty_cart_returns_error(): void
    {
        $this->cart->items()->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'shipping_address' => 'Test Address',
                'payment_method'   => 'cod',
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function unauthenticated_user_cannot_checkout(): void
    {
        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address' => 'Test Address',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_get_shipping_options(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/checkout/shipping-options');

        $response->assertOk();
    }

    /** @test */
    public function user_can_calculate_tax(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/checkout/calculate-tax', [
                'country' => 'Bangladesh',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['subtotal', 'tax_rate', 'tax_amount'],
            ]);
    }

    /** @test */
    public function order_clears_cart_after_checkout(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'shipping_address' => '123 Test St',
                'payment_method'   => 'cod',
            ]);

        $this->assertDatabaseMissing('cart_items', [
            'cart_id' => $this->cart->id,
        ]);
    }

    /** @test */
    public function checkout_decrements_product_stock(): void
    {
        $initialStock = $this->product->stock_quantity;

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/checkout', [
                'shipping_address' => '123 Test St',
                'payment_method'   => 'cod',
            ]);

        $this->assertEquals(
            $initialStock - 2,
            $this->product->fresh()->stock_quantity
        );
    }
}
