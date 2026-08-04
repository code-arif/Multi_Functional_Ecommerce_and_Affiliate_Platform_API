<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Auth\Models\User;
use Modules\Product\Models\Product;;
use Modules\Catalog\Models\VendorProductPrice;
use Modules\Inventory\Models\InventoryLog;
use Modules\Inventory\Models\Warehouse;
use Modules\Vendor\Models\Vendor;

uses(Tests\TestCase::class)->use(DatabaseTransactions::class);

// ─── Helpers ─────────────────────────────────────────────────────

if (!function_exists('createAdminUser')) {
    function createAdminUser(): User
    {
        $user = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status'   => 'active',
        ]);
        $user->assignRole('super-admin');
        return $user;
    }
}

beforeEach(function () {
    $this->seed(\Modules\RBAC\Database\Seeders\RBACSeeder::class);

    // Create vendor user
    $this->vendorUser = User::create([
        'name'     => 'Test Vendor',
        'email'    => 'vendor-inv-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status'   => 'active',
    ]);

    // Create vendor
    $this->vendor = Vendor::create([
        'user_id'   => $this->vendorUser->id,
        'shop_name' => 'Inventory Test Shop',
        'slug'      => 'inventory-test-shop-' . uniqid(),
        'status'    => 'active',
    ]);

    // Create product
    $this->product = Product::create([
        'name'            => 'Test Product',
        'slug'            => 'test-product-' . uniqid(),
        'sku'             => 'TEST-' . uniqid(),
        'type'            => 'simple',
        'price'           => 100.00,
        'stock_quantity'  => 50,
        'stock_status'    => 'in_stock',
        'status'          => 'active',
    ]);

    // Create vendor-product price entry
    $this->vendorProduct = VendorProductPrice::create([
        'vendor_id'      => $this->vendor->id,
        'product_id'     => $this->product->id,
        'price'          => 100.00,
        'stock_quantity' => 50,
        'stock_status'   => 'in_stock',
        'is_active'      => true,
    ]);
});

// ─── Warehouse CRUD ─────────────────────────────────────────────

it('creates a warehouse', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/warehouses', [
            'name'          => 'Main Warehouse',
            'address_line_1' => '123 Street',
            'city'          => 'Dhaka',
            'is_default'    => true,
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Main Warehouse');
});

it('lists warehouses for vendor', function () {
    Warehouse::create([
        'vendor_id' => $this->vendor->id,
        'name'      => 'Warehouse 1',
        'slug'      => 'wh1-' . uniqid(),
    ]);

    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/warehouses');

    $response->assertOk();
});

it('rejects non-vendor from creating warehouse', function () {
    $user = User::create([
        'name'     => 'Customer',
        'email'    => 'customer-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status'   => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/vendor/warehouses', [
            'name' => 'My Warehouse',
        ]);

    $response->assertStatus(403);
});

// ─── Stock Adjustment ──────────────────────────────────────────

it('increases stock', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/inventory/adjust', [
            'vendor_product_uuid' => $this->vendorProduct->uuid,
            'quantity'          => 10,
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
});

it('decreases stock', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/inventory/adjust', [
            'vendor_product_uuid' => $this->vendorProduct->uuid,
            'quantity'          => -5,
            'notes'             => 'Damaged item removed',
        ]);

    $response->assertOk();
    $this->assertEquals(45, $this->vendorProduct->fresh()->stock_quantity);

    $this->assertDatabaseHas('inventory_logs', [
        'product_id' => $this->product->id,
        'quantity'   => -5,
    ]);
});

it('rejects zero quantity adjustment', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/inventory/adjust', [
            'vendor_product_uuid' => $this->vendorProduct->uuid,
            'quantity'          => 0,
        ]);

    $response->assertStatus(422);
});

it('rejects invalid product id', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->postJson('/api/v1/vendor/inventory/adjust', [
            'vendor_product_uuid' => '00000000-0000-0000-0000-000000000000',
            'quantity'          => 10,
        ]);

    $response->assertStatus(422);
});

it('views inventory summary', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/inventory/summary');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['total_products', 'low_stock', 'out_of_stock', 'in_stock'],
        ]);
});

it('views inventory list', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->getJson('/api/v1/vendor/inventory');

    $response->assertOk();
});

it('views stock logs', function () {
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
});

it('rejects unauthenticated access to inventory', function () {
    $response = $this->getJson('/api/v1/vendor/inventory');
    $response->assertStatus(401);
});

// ─── Stock Transfer ────────────────────────────────────────────

it('transfers stock between warehouses', function () {
    $fromWarehouse = Warehouse::create([
        'vendor_id'  => $this->vendor->id,
        'name'       => 'From WH',
        'slug'       => 'from-wh-' . uniqid(),
        'is_default' => true,
    ]);

    $toWarehouse = Warehouse::create([
        'vendor_id' => $this->vendor->id,
        'name'      => 'To WH',
        'slug'      => 'to-wh-' . uniqid(),
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
});

// ─── Admin Routes ─────────────────────────────────────────────

it('allows admin to view inventory summary', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/inventory/summary');

    $response->assertOk()
        ->assertJsonStructure(['data' => [
            'total_products', 'in_stock', 'out_of_stock', 'on_backorder',
        ]]);
});

it('rejects non-admin from admin inventory', function () {
    $response = $this->actingAs($this->vendorUser, 'sanctum')
        ->getJson('/api/v1/admin/inventory/summary');

    // The admin middleware should reject non-admin users
    expect(in_array($response->status(), [401, 403]))->toBeTrue();
});
