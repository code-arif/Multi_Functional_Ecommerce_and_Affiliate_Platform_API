<?php

namespace Modules\Promotions\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\Category;
use Modules\Vendor\Models\Vendor;
use Modules\Promotions\Models\Promotion;
use Modules\Promotions\Models\PromotionUsage;
use Modules\Promotions\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PromotionApiTest extends TestCase
{
    use RefreshDatabase;

    private Promotion $flashSale;
    private Promotion $activePromotion;
    private Promotion $expiredPromotion;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $vendor = Vendor::factory()->create([
            'shop_name' => 'Promo Vendor',
            'slug'      => 'promo-vendor',
            'status'    => 'active',
        ]);

        $category = Category::factory()->create([
            'name' => 'Promo Cat',
            'slug' => 'promo-cat',
        ]);

        $this->product = Product::factory()->create([
            'vendor_id'   => $vendor->id,
            'category_id' => $category->id,
            'name'        => 'Promo Product',
            'slug'        => 'promo-product',
            'price'       => 100.00,
            'status'      => 'active',
        ]);

        $this->flashSale = Promotion::create([
            'name'           => 'Summer Sale',
            'type'           => 'flash_sale',
            'discount_type'  => 'percentage',
            'discount_value' => 20,
            'applies_to'     => 'all',
            'is_active'      => true,
            'starts_at'      => now()->subDay(),
            'ends_at'        => now()->addDay(),
            'badge_text'     => '20% OFF',
        ]);

        $this->activePromotion = Promotion::create([
            'name'           => 'Active Promo',
            'type'           => 'seasonal',
            'discount_type'  => 'fixed',
            'discount_value' => 50,
            'applies_to'     => 'all',
            'is_active'      => true,
            'starts_at'      => now()->subDay(),
            'ends_at'        => now()->addDay(),
        ]);

        $this->expiredPromotion = Promotion::create([
            'name'           => 'Expired Promo',
            'type'           => 'flash_sale',
            'discount_type'  => 'percentage',
            'discount_value' => 50,
            'applies_to'     => 'all',
            'is_active'      => true,
            'starts_at'      => now()->subDays(10),
            'ends_at'        => now()->subDays(5),
        ]);
    }

    /** @test */
    public function public_can_list_active_promotions(): void
    {
        $response = $this->getJson('/api/v1/promotions');

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // Only active ones (flashSale + activePromotion)
    }

    /** @test */
    public function public_can_list_flash_sales(): void
    {
        $response = $this->getJson('/api/v1/promotions/flash-sales');

        $response->assertOk()
            ->assertJsonCount(1, 'data')  // Only the flash sale
            ->assertJsonPath('data.0.type', 'flash_sale');
    }

    /** @test */
    public function public_can_list_upcoming_promotions(): void
    {
        // Add an upcoming promotion
        Promotion::create([
            'name'           => 'Upcoming Sale',
            'type'           => 'seasonal',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'applies_to'     => 'all',
            'is_active'      => true,
            'starts_at'      => now()->addDays(5),
            'ends_at'        => now()->addDays(10),
        ]);

        $response = $this->getJson('/api/v1/promotions/upcoming');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Upcoming Sale');
    }

    /** @test */
    public function promotion_has_computed_discount_label(): void
    {
        $this->assertEquals('20% OFF', $this->flashSale->discount_label);

        $bxgy = Promotion::create([
            'name'          => 'BXGY',
            'type'          => 'buy_x_get_y',
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'min_quantity'  => 2,
            'free_quantity' => 1,
            'applies_to'    => 'all',
            'is_active'     => true,
            'starts_at'     => now(),
            'ends_at'       => now()->addDay(),
        ]);

        $this->assertEquals('Buy 2 Get 1 Free', $bxgy->discount_label);

        $freeShipping = Promotion::create([
            'name'           => 'Free Shipping',
            'type'           => 'free_shipping',
            'discount_type'  => 'fixed',
            'discount_value' => 0,
            'applies_to'     => 'all',
            'is_active'      => true,
            'starts_at'      => now(),
            'ends_at'        => now()->addDay(),
        ]);

        $this->assertEquals('Free Shipping', $freeShipping->discount_label);
    }

    /** @test */
    public function promotion_is_currently_active_check(): void
    {
        $this->assertTrue($this->flashSale->is_currently_active);
        $this->assertFalse($this->expiredPromotion->is_currently_active);

        $inactive = Promotion::create([
            'name'           => 'Inactive',
            'type'           => 'flash_sale',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'applies_to'     => 'all',
            'is_active'      => false,
            'starts_at'      => now()->subDay(),
            'ends_at'        => now()->addDay(),
        ]);

        $this->assertFalse($inactive->is_currently_active);
    }

    /** @test */
    public function does_not_return_expired_promotions(): void
    {
        $response = $this->getJson('/api/v1/promotions');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertNotContains('Expired Promo', $names);
    }

    /** @test */
    public function applies_to_product_check(): void
    {
        $promotion = Promotion::create([
            'name'           => 'Product Specific',
            'type'           => 'flash_sale',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'applies_to'     => 'products',
            'product_ids'    => [$this->product->id],
            'is_active'      => true,
            'starts_at'      => now(),
            'ends_at'        => now()->addDay(),
        ]);

        $this->assertTrue($promotion->appliesToProduct($this->product));

        $otherProduct = Product::factory()->create([
            'name'   => 'Other Product',
            'slug'   => 'other-product',
            'price'  => 50,
            'status' => 'active',
        ]);

        $this->assertFalse($promotion->appliesToProduct($otherProduct));
    }

    /** @test */
    public function tiered_discount_value_calculation(): void
    {
        $promotion = Promotion::create([
            'name'           => 'Tiered',
            'type'           => 'tiered_discount',
            'discount_type'  => 'percentage',
            'discount_value' => 5,
            'tiers'          => [
                ['from' => 2, 'value' => 10],
                ['from' => 5, 'value' => 15],
                ['from' => 10, 'value' => 20],
            ],
            'applies_to'     => 'all',
            'is_active'      => true,
            'starts_at'      => now(),
            'ends_at'        => now()->addDay(),
        ]);

        // Quantity 1 → default (5%)
        $this->assertEquals(5, $promotion->getTieredDiscountValue(1));
        // Quantity 3 → tier 10% ($gte 2)
        $this->assertEquals(10, $promotion->getTieredDiscountValue(3));
        // Quantity 7 → tier 15% ($gte 5)
        $this->assertEquals(15, $promotion->getTieredDiscountValue(7));
        // Quantity 15 → tier 20% ($gte 10)
        $this->assertEquals(20, $promotion->getTieredDiscountValue(15));
    }

    /** @test */
    public function usage_limits_are_enforced(): void
    {
        $promotion = Promotion::create([
            'name'           => 'Limited',
            'type'           => 'flash_sale',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'usage_limit'    => 3,
            'applies_to'     => 'all',
            'is_active'      => true,
            'starts_at'      => now(),
            'ends_at'        => now()->addDay(),
        ]);

        $this->assertFalse($promotion->hasReachedUsageLimit());

        $promotion->update(['used_count' => 3]);

        $this->assertTrue($promotion->fresh()->hasReachedUsageLimit());
    }

    /** @test */
    public function validation_validates_active_promotion(): void
    {
        $service = app(PromotionService::class);
        $user = User::factory()->create(['email' => 'test@example.com', 'status' => 'active']);

        $result = $service->validatePromotion($this->flashSale, $user, []);

        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function validation_rejects_expired_promotion(): void
    {
        $service = app(PromotionService::class);
        $user = User::factory()->create(['email' => 'test2@example.com', 'status' => 'active']);

        $result = $service->validatePromotion($this->expiredPromotion, $user, []);

        $this->assertFalse($result['valid']);
    }

    /** @test */
    public function analytics_returns_summary(): void
    {
        $service = app(PromotionService::class);

        // Record some usage
        $user = User::factory()->create(['email' => 'analytics@example.com', 'status' => 'active']);
        $service->recordUsage($this->flashSale, $user, 1, 200.00);

        $analytics = $service->getAnalytics();

        $this->assertArrayHasKey('total_promotions', $analytics);
        $this->assertArrayHasKey('total_usage', $analytics);
        $this->assertArrayHasKey('total_discount_given', $analytics);
        $this->assertArrayHasKey('usage_by_type', $analytics);
        $this->assertArrayHasKey('top_promotions', $analytics);
        $this->assertEquals(200.00, $analytics['total_discount_given']);
    }
}
