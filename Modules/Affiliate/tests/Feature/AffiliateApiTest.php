<?php

namespace Modules\Affiliate\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Affiliate\Models\AffiliateProduct;
use Modules\Affiliate\Models\AffiliateConversion;
use Modules\Affiliate\Models\AffiliateEarning;
use Modules\Affiliate\Models\AffiliateClick;
use Modules\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AffiliateApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AffiliateProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Affiliate User',
            'email'  => 'affiliate@example.com',
            'status' => 'active',
        ]);

        $this->product = AffiliateProduct::create([
            'title'            => 'Test Affiliate Product',
            'slug'             => 'test-affiliate-product',
            'affiliate_link'   => 'https://example.com/product',
            'source_platform'  => 'amazon',
            'display_price'    => 100.00,
            'commission_type'  => 'percentage',
            'commission_value' => 10,
            'is_active'        => true,
        ]);
    }

    /** @test */
    public function public_can_list_affiliate_products(): void
    {
        $response = $this->getJson('/api/v1/affiliate');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Test Affiliate Product');
    }

    /** @test */
    public function public_can_view_affiliate_product_detail(): void
    {
        $response = $this->getJson("/api/v1/affiliate/{$this->product->slug}");

        $response->assertOk()
            ->assertJsonPath('data.commission_type', 'percentage')
            ->assertJsonPath('data.commission_value', 10);
    }

    /** @test */
    public function public_can_track_click(): void
    {
        $response = $this->postJson("/api/v1/affiliate/{$this->product->slug}/click");

        $response->assertOk()
            ->assertJsonPath('data.url', 'https://example.com/product');

        $this->assertEquals(1, $this->product->fresh()->click_count);
    }

    /** @test */
    public function affiliate_can_view_dashboard(): void
    {
        AffiliateClick::create([
            'affiliate_product_id' => $this->product->id,
            'user_id'             => $this->user->id,
            'ip_address'          => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/affiliate/dashboard');

        $response->assertOk()
            ->assertJsonStructure(['data' => [
                'total_clicks', 'total_conversions', 'approved_conversions',
                'conversion_rate', 'available_balance', 'lifetime_earnings',
                'pending_earnings', 'total_paid',
            ]])
            ->assertJsonPath('data.total_clicks', 1);
    }

    /** @test */
    public function affiliate_can_view_earnings(): void
    {
        AffiliateEarning::create([
            'user_id' => $this->user->id,
            'affiliate_product_id' => $this->product->id,
            'amount'  => 100.00,
            'type'    => 'commission',
            'status'  => 'available',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/affiliate/earnings');

        $response->assertOk();
    }

    /** @test */
    public function commission_calculation_percentage(): void
    {
        $service = app(\Modules\Affiliate\Services\AffiliateService::class);

        $commission = $service->calculateCommission($this->product, 200.00);

        $this->assertEquals(20.00, $commission); // 10% of 200
    }

    /** @test */
    public function commission_calculation_fixed(): void
    {
        $product = AffiliateProduct::create([
            'title'            => 'Fixed Commission',
            'slug'             => 'fixed-commission',
            'affiliate_link'   => 'https://example.com/fixed',
            'source_platform'  => 'daraz',
            'display_price'    => 100.00,
            'commission_type'  => 'fixed',
            'commission_value' => 15,
            'is_active'        => true,
        ]);

        $service = app(\Modules\Affiliate\Services\AffiliateService::class);

        $commission = $service->calculateCommission($product, 200.00);

        $this->assertEquals(15.00, $commission); // Fixed 15
    }

    /** @test */
    public function conversion_records_earnings(): void
    {
        $service = app(\Modules\Affiliate\Services\AffiliateService::class);
        $order = Order::create([
            'user_id'       => $this->user->id,
            'order_number'  => 'ORD-AFF-TEST',
            'subtotal'      => 200.00,
            'total'         => 200.00,
            'status'        => 'delivered',
            'payment_status' => 'paid',
            'tracking_token' => 'tok-aff-test',
        ]);

        $conversion = $service->recordConversion($this->product, $this->user, $order);

        $this->assertEquals(20.00, $conversion->commission_amount);
        $this->assertEquals('pending', $conversion->status);

        // Check earning was created
        $this->assertEquals(1, AffiliateEarning::where('user_id', $this->user->id)->count());
        $this->assertEquals(20.00, AffiliateEarning::first()->amount);
    }

    /** @test */
    public function approve_conversion_releases_earnings(): void
    {
        $service = app(\Modules\Affiliate\Services\AffiliateService::class);
        $order = Order::create([
            'user_id'       => $this->user->id,
            'order_number'  => 'ORD-AFF-APPROVE',
            'subtotal'      => 200.00,
            'total'         => 200.00,
            'status'        => 'delivered',
            'payment_status' => 'paid',
            'tracking_token' => 'tok-aff-approve',
        ]);

        $conversion = $service->recordConversion($this->product, $this->user, $order);
        $service->approveConversion($conversion);

        $this->assertEquals('approved', $conversion->fresh()->status);
        $this->assertEquals('available', AffiliateEarning::first()->status);
    }

    /** @test */
    public function affiliate_earnings_balance_is_accurate(): void
    {
        $service = app(\Modules\Affiliate\Services\AffiliateService::class);

        AffiliateEarning::create([
            'user_id' => $this->user->id,
            'affiliate_product_id' => $this->product->id,
            'amount'  => 50.00,
            'type'    => 'commission',
            'status'  => 'available',
        ]);
        AffiliateEarning::create([
            'user_id' => $this->user->id,
            'affiliate_product_id' => $this->product->id,
            'amount'  => 30.00,
            'type'    => 'commission',
            'status'  => 'available',
        ]);

        $balance = $service->getAvailableBalance($this->user->id);
        $this->assertEquals(80.00, $balance);

        $lifetime = $service->getLifetimeEarnings($this->user->id);
        $this->assertEquals(80.00, $lifetime);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/affiliate/dashboard');

        $response->assertStatus(401);
    }
}
