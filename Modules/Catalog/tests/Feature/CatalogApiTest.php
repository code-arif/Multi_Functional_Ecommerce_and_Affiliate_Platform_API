<?php

namespace Modules\Catalog\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Product;
use Modules\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;
    private Brand $brand;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Test Customer',
            'email'  => 'customer@example.com',
            'status' => 'active',
        ]);

        $this->category = Category::factory()->create([
            'name'      => 'Electronics',
            'slug'      => 'electronics',
            'is_active' => true,
        ]);

        $this->brand = Brand::factory()->create([
            'name'      => 'Apple',
            'slug'      => 'apple',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'brand_id'    => $this->brand->id,
            'name'        => 'iPhone 15 Pro',
            'slug'        => 'iphone-15-pro',
            'sku'         => 'IP15P-256',
            'type'        => 'simple',
            'price'       => 1199.00,
            'status'      => 'active',
        ]);
    }

    // ─── Categories ───────────────────────────────────────────────

    /** @test */
    public function guest_can_list_categories(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['uuid', 'name', 'slug'],
                ],
            ]);
    }

    /** @test */
    public function guest_can_view_category_by_slug(): void
    {
        $response = $this->getJson("/api/v1/categories/{$this->category->slug}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Electronics');
    }

    /** @test */
    public function guest_gets_404_for_inactive_category(): void
    {
        $inactive = Category::factory()->create([
            'name'      => 'Hidden',
            'slug'      => 'hidden',
            'is_active' => false,
        ]);

        $response = $this->getJson("/api/v1/categories/{$inactive->slug}");

        $response->assertStatus(404);
    }

    // ─── Products ─────────────────────────────────────────────────

    /** @test */
    public function guest_can_list_active_products(): void
    {
        Product::factory()->create(['status' => 'inactive', 'slug' => 'hidden-product']);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function guest_can_view_product_by_slug(): void
    {
        $response = $this->getJson("/api/v1/products/{$this->product->slug}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'iPhone 15 Pro');
    }

    /** @test */
    public function guest_gets_404_for_inactive_product(): void
    {
        $inactive = Product::factory()->create([
            'status' => 'inactive',
            'slug'   => 'inactive-product',
        ]);

        $response = $this->getJson("/api/v1/products/{$inactive->slug}");

        $response->assertStatus(404);
    }

    /** @test */
    public function guest_can_view_featured_products(): void
    {
        $response = $this->getJson('/api/v1/products/featured');

        $response->assertOk();
    }

    /** @test */
    public function guest_can_view_new_arrivals(): void
    {
        $response = $this->getJson('/api/v1/products/new-arrivals');

        $response->assertOk();
    }

    /** @test */
    public function guest_can_view_bestsellers(): void
    {
        $response = $this->getJson('/api/v1/products/bestsellers');

        $response->assertOk();
    }

    /** @test */
    public function guest_can_view_related_products(): void
    {
        $response = $this->getJson("/api/v1/products/{$this->product->slug}/related");

        $response->assertOk();
    }

    /** @test */
    public function guest_can_search_products(): void
    {
        $response = $this->getJson('/api/v1/search?q=iPhone');

        $response->assertOk();
    }

    /** @test */
    public function search_requires_minimum_query_length(): void
    {
        $response = $this->getJson('/api/v1/search?q=a');

        $response->assertStatus(422);
    }

    // ─── Brands (via product response) ────────────────────────────

    /** @test */
    public function product_response_includes_brand_data(): void
    {
        $response = $this->getJson("/api/v1/products/{$this->product->slug}");

        $response->assertOk()
            ->assertJsonPath('data.brand.name', 'Apple');
    }

    // ─── Vendor-Product Mapping (SRS Section 4.5) ─────────────────

    /** @test */
    public function product_can_have_vendor_pricing(): void
    {
        $vendorUser = User::factory()->create(['email' => 'vendor-price@example.com', 'status' => 'active']);
        $vendor = Vendor::factory()->create([
            'user_id'   => $vendorUser->id,
            'shop_name' => 'Test Vendor',
            'slug'      => 'test-vendor',
            'status'    => 'active',
        ]);

        $this->product->vendorProductPrices()->create([
            'vendor_id'      => $vendor->id,
            'price'          => 1099.00,
            'sale_price'     => 999.00,
            'stock_quantity' => 25,
            'is_active'      => true,
        ]);

        $this->assertDatabaseHas('vendor_product_prices', [
            'vendor_id'  => $vendor->id,
            'product_id' => $this->product->id,
            'price'      => 1099.00,
        ]);

        $this->assertCount(1, $this->product->fresh()->vendors);
    }

    // ─── Product Approval Workflow (SRS Section 4.5) ──────────────

    /** @test */
    public function pending_product_can_be_approved(): void
    {
        $pendingProduct = Product::factory()->create([
            'status' => 'pending',
            'slug'   => 'pending-product',
        ]);

        $this->assertEquals('pending', $pendingProduct->status);

        $pendingProduct->approve();

        $this->assertEquals('active', $pendingProduct->fresh()->status);
        $this->assertNotNull($pendingProduct->fresh()->published_at);
    }

    /** @test */
    public function pending_product_can_be_rejected(): void
    {
        $pendingProduct = Product::factory()->create([
            'status' => 'pending',
            'slug'   => 'rejected-product',
        ]);

        $pendingProduct->reject();

        $this->assertEquals('inactive', $pendingProduct->fresh()->status);
    }
}
