<?php

namespace Modules\Inventory\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\VendorProductPrice;
use Modules\Inventory\Models\InventoryLog;
use Modules\Inventory\Models\Warehouse;
use Modules\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $vendorUser;
    private Vendor $vendor;
    private Product $product;
    private VendorProductPrice $vendorProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorUser = User::factory()->create([
            'name'   => 'Test Vendor',
            'email'  => 'vendor-inv@example.com',
            'status' => 'active',
        ]);

        $this->vendor = Vendor::factory()->create([
            'user_id'   => $this->vendorUser->id,
            'shop_name' => 'Inventory Test Shop',
            'slug'      => 'inventory-test-shop',
            'status'    => 'active',
        ]);

        $this->product = Product::factory()->create([
            'name'            => 'Test Product',
            'slug'            => 'test-product',
            'sku'             => 'TEST-001',
            'type'            => 'simple',
            'price'           => 100.00,
            'stock_quantity'  => 50,
            'stock_status'    => 'in_stock',
            'status'          => 'active',
        ]);

        $this->vendorProduct = VendorProductPrice::create([
            'vendor_id'      => $this->vendor->id,
            'product_id'     => $this->product->id,
            'price'          => 100.00,
            'stock_quantity' => 50,
            'stock_status'   => 'in_stock',
            'is_active'      => true,
        ]);
    }

    // ─── Warehouse CRUD ───────────────────────────────────────────

    /** @test */
    public function vendor_can_create_warehouse(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->postJson('/api/v1/vendor/warehouses', [
                'name'          => 'Main Warehouse',
                'address_line_1' => '123 Street',
                'city'          => 'Dhaka',
                'is_default'    => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Main Warehouse');
    }

    /** @test */
    public function vendor_can_list_warehouses(): void
    {
        Warehouse::factory()->create([
            'vendor_id' => $this->vendor->id,
            'name'      => 'Warehouse 1',
            'slug'      => 'wh1',
        ]);

        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->getJson('/api/v1/vendor/warehouses');

        $response->assertOk();
    }

    /** @test */
    public function non_vendor_cannot_create_warehouse(): void
    {
        $user = User::factory()->create(['email' => 'customer2@example.com', 'status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/vendor/warehouses', [
                'name' => 'My Warehouse',
            ]);

        $response->assertStatus(403);
    }

    // ─── Inventory Adjustment ─────────────────────────────────────

    /** @test */
    public function vendor_can_adjust_stock(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->postJson('/api/v1/vendor/inventory/adjust', [
                'vendor_product_id' => $this->vendorProduct->id,
                'quantity'          => 10,  // Increase by 10
                'notes'             => 'Restock from supplier',
            ]);

        $response->assertOk();

        // Verify stock was updated
        $this->assertEquals(60, $this->vendorProduct->fresh()->stock_quantity);

        // Verify log was created
        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $this->product->id,
            'vendor_id'  => $this->vendor->id,
            'type'       => 'adjustment',
            'quantity'   => 10,
        ]);
    }

    /** @test */
    public function vendor_can_decrease_stock(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->postJson('/api/v1/vendor/inventory/adjust', [
                'vendor_product_id' => $this->vendorProduct->id,
                'quantity'          => -5,
                'notes'             => 'Damaged item removed',
            ]);

        $response->assertOk();

        $this->assertEquals(45, $this->vendorProduct->fresh()->stock_quantity);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $this->product->id,
            'quantity'   => -5,
        ]);
    }

    /** @test */
    public function vendor_can_view_inventory_summary(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->getJson('/api/v1/vendor/inventory/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['total_products', 'low_stock', 'out_of_stock', 'in_stock'],
            ]);
    }

    /** @test */
    public function vendor_can_view_inventory_list(): void
    {
        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->getJson('/api/v1/vendor/inventory');

        $response->assertOk();
    }

    /** @test */
    public function vendor_can_view_stock_logs(): void
    {
        // Create a log entry first
        InventoryLog::create([
            'product_id'   => $this->product->id,
            'vendor_id'    => $this->vendor->id,
            'type'         => 'adjustment',
            'quantity'     => 10,
            'stock_before' => 50,
            'stock_after'  => 60,
            'notes'        => 'Test log',
        ]);

        $response = $this->actingAs($this->vendorUser, 'sanctum')
            ->getJson('/api/v1/vendor/inventory/logs');

        $response->assertOk();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_inventory(): void
    {
        $response = $this->getJson('/api/v1/vendor/inventory');
        $response->assertStatus(401);
    }

    // ─── Stock Transfer ───────────────────────────────────────────

    /** @test */
    public function inventory_service_can_transfer_stock(): void
    {
        $fromWarehouse = Warehouse::factory()->create([
            'vendor_id' => $this->vendor->id,
            'name'      => 'From WH',
            'slug'      => 'from-wh',
            'is_default' => true,
        ]);
        $toWarehouse = Warehouse::factory()->create([
            'vendor_id' => $this->vendor->id,
            'name'      => 'To WH',
            'slug'      => 'to-wh',
        ]);

        $service = app(\Modules\Inventory\Services\InventoryService::class);
        $service->transferStock($this->product, $fromWarehouse, $toWarehouse, 10);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id'   => $this->product->id,
            'warehouse_id' => $fromWarehouse->id,
            'type'         => 'transfer_out',
            'quantity'     => -10,
        ]);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id'   => $this->product->id,
            'warehouse_id' => $toWarehouse->id,
            'type'         => 'transfer_in',
            'quantity'     => 10,
        ]);
    }
}
