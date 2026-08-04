<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Search\Models\SearchLog;
use Modules\Product\Models\Product;;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Brand;
use Modules\Auth\Models\User;

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
    // Seed permissions and roles (CACHE_STORE=array in .env for tag support)
    $this->seed(\Modules\RBAC\Database\Seeders\RBACSeeder::class);

    // Create test data manually (no factories available)
    $category = Category::create([
        'name'      => 'Electronics',
        'slug'      => 'electronics-' . uniqid(),
        'is_active' => true,
    ]);

    $brand = Brand::create([
        'name'      => 'TestBrand',
        'slug'      => 'testbrand-' . uniqid(),
        'is_active' => true,
    ]);

    // Create active products with searchable content
    for ($i = 0; $i < 5; $i++) {
        Product::create([
            'category_id'      => $category->id,
            'brand_id'         => $brand->id,
            'status'           => 'active',
            'name'             => 'iPhone 15 Pro ' . ($i + 1),
            'slug'             => 'iphone-15-pro-' . ($i + 1) . '-' . uniqid(),
            'sku'              => 'IP15P-' . uniqid(),
            'price'            => 999.99,
            'stock_status'     => 'in_stock',
            'stock_quantity'   => 10,
            'short_description' => 'Latest iPhone with A17 Pro chip',
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        Product::create([
            'category_id'      => $category->id,
            'brand_id'         => $brand->id,
            'status'           => 'active',
            'name'             => 'Samsung Galaxy S24 ' . ($i + 1),
            'slug'             => 'samsung-galaxy-s24-' . ($i + 1) . '-' . uniqid(),
            'sku'              => 'SGS24-' . uniqid(),
            'price'            => 899.99,
            'stock_status'     => 'in_stock',
            'stock_quantity'   => 5,
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        Product::create([
            'category_id'      => $category->id,
            'brand_id'         => $brand->id,
            'status'           => 'active',
            'name'             => 'MacBook Air M3',
            'slug'             => 'macbook-air-m3-' . uniqid(),
            'sku'              => 'MBA-M3-' . uniqid(),
            'price'            => 1299.99,
            'stock_status'     => 'out_of_stock',
            'stock_quantity'   => 0,
        ]);
    }

    // Inactive product should NOT appear in search
    Product::create([
        'category_id'      => $category->id,
        'brand_id'         => $brand->id,
        'status'           => 'inactive',
        'name'             => 'Hidden Product',
        'slug'             => 'hidden-product-' . uniqid(),
        'sku'              => 'HIDDEN-' . uniqid(),
        'price'            => 49.99,
        'stock_status'     => 'in_stock',
        'stock_quantity'   => 1,
    ]);
});

// ─── Public Search Endpoints ────────────────────────────────────────

it('searches products by keyword (DB fallback)', function () {
    $response = $this->getJson('/api/v1/search/?q=iPhone');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data',
            'pagination' => ['total', 'per_page', 'current_page', 'last_page'],
        ]);

    expect($response['success'])->toBeTrue();
    expect($response['pagination']['total'])->toBeGreaterThanOrEqual(1);
});

it('returns empty results for non-matching queries', function () {
    $response = $this->getJson('/api/v1/search/?q=xyznonexistent123');

    $response->assertStatus(200);
    expect($response['data'])->toBeEmpty();
    expect($response['pagination']['total'])->toBe(0);
});

it('filters products by category', function () {
    $category = Category::first();
    $response = $this->getJson('/api/v1/search/?q=phone&category_id=' . $category->id);

    $response->assertStatus(200);
    expect($response['success'])->toBeTrue();
});

it('filters products by stock status', function () {
    $response = $this->getJson('/api/v1/search/?q=MacBook&in_stock=1');

    $response->assertStatus(200);
    // MacBook Air M3 is out_of_stock, so filtered out by in_stock=1
    expect($response['pagination']['total'])->toBe(0);
});

it('sorts products by price ascending', function () {
    $response = $this->getJson('/api/v1/search/?q=phone&sort=price_asc');

    $response->assertStatus(200);
    expect($response['success'])->toBeTrue();
});

it('validates minimum query length', function () {
    $response = $this->getJson('/api/v1/search/?q=a');

    expect($response->status())->toBe(422); // min:2 validation
});

it('rejects queries exceeding max length', function () {
    $response = $this->getJson('/api/v1/search/?q=' . str_repeat('a', 201));

    expect($response->status())->toBe(422); // max:200 validation
});

// ─── Suggestions ────────────────────────────────────────────────────

it('returns autocomplete suggestions', function () {
    $response = $this->getJson('/api/v1/search/suggestions?q=iPhone');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data']);

    expect($response['success'])->toBeTrue();
    expect($response['data'])->toBeArray();
});

it('validates suggestion query', function () {
    $response = $this->getJson('/api/v1/search/suggestions');
    expect($response->status())->toBe(422); // q is required
});

// ─── Price Range ────────────────────────────────────────────────────

it('returns price range for products', function () {
    $response = $this->getJson('/api/v1/search/price-range');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data' => ['min', 'max']]);

    expect($response['success'])->toBeTrue();
    expect($response['data']['min'])->toBeGreaterThanOrEqual(0);
    expect($response['data']['max'])->toBeGreaterThanOrEqual($response['data']['min']);
});

// ─── Facets ─────────────────────────────────────────────────────────

it('returns faceted search data', function () {
    $response = $this->getJson('/api/v1/search/facets?q=phone');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data' => [
            'categories', 'brands', 'price_range', 'ratings',
        ]]);

    expect($response['success'])->toBeTrue();
});

// ─── Popular Searches ───────────────────────────────────────────────

it('returns popular search queries', function () {
    // Create some search log entries
    SearchLog::create([
        'query'            => 'iPhone',
        'normalized_query' => SearchLog::normalize('iPhone'),
        'results_count'    => 5,
        'has_results'      => true,
        'source'           => 'web',
    ]);

    SearchLog::create([
        'query'            => 'Samsung',
        'normalized_query' => SearchLog::normalize('Samsung'),
        'results_count'    => 3,
        'has_results'      => true,
        'source'           => 'web',
    ]);

    $response = $this->getJson('/api/v1/search/popular');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data']);

    expect($response['success'])->toBeTrue();
});

// ─── Admin Endpoints ────────────────────────────────────────────────

it('returns search analytics for admin', function () {
    $admin = createAdminUser();

    // Create search logs for analytics
    SearchLog::create([
        'query'            => 'test query',
        'normalized_query' => 'test query',
        'results_count'    => 5,
        'has_results'      => true,
        'search_duration_ms' => 12.5,
        'source'           => 'web',
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/search/analytics?period=30_days');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data' => [
            'total_searches', 'unique_queries', 'zero_results',
            'avg_duration_ms', 'popular', 'zero_result_queries',
        ]]);
});

it('returns search status for admin', function () {
    $admin = createAdminUser();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/search/status');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data' => [
            'available', 'index_exists', 'index_name', 'fallback_active',
        ]]);
});

it('rejects unauthenticated admin search routes', function () {
    $response = $this->getJson('/api/v1/admin/search/analytics');
    expect($response->status())->toBe(401);
});

it('rejects non-admin users from admin search routes', function () {
    $user = User::create([
        'name'     => 'Regular User',
        'email'    => 'regular-' . uniqid() . '@example.com',
        'password' => bcrypt('password'),
        'status'   => 'active',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/search/analytics');

    // Doesn't have admin role or search.view permission
    expect(in_array($response->status(), [401, 403]))->toBeTrue();
});

it('returns zero-result searches for admin', function () {
    $admin = createAdminUser();

    // Create zero-result search logs
    SearchLog::create([
        'query'            => 'nonexistent product',
        'normalized_query' => 'nonexistent product',
        'results_count'    => 0,
        'has_results'      => false,
        'source'           => 'web',
    ]);

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/search/zero-results');

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'data']);
    expect($response['success'])->toBeTrue();
});
