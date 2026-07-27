<?php

namespace Modules\Vendor\Tests\Feature;

use Tests\TestCase;
use Modules\Auth\Models\User;
use Modules\Vendor\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VendorApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'   => 'Test Customer',
            'email'  => 'customer@example.com',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'name'   => 'Admin User',
            'email'  => 'admin@example.com',
            'status' => 'active',
        ]);
    }

    // ─── Public Routes (No Auth) ──────────────────────────────────

    /** @test */
    public function guest_can_list_active_vendors(): void
    {
        Vendor::factory()->create([
            'shop_name' => 'Active Shop',
            'status'    => 'active',
            'slug'      => 'active-shop',
        ]);

        Vendor::factory()->create([
            'shop_name' => 'Pending Shop',
            'status'    => 'pending',
            'slug'      => 'pending-shop',
        ]);

        $response = $this->getJson('/api/v1/vendors');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'shop_name', 'slug', 'status'],
                ],
            ]);
    }

    /** @test */
    public function guest_can_view_active_vendor_by_slug(): void
    {
        $vendor = Vendor::factory()->create([
            'shop_name' => 'Active Shop',
            'status'    => 'active',
            'slug'      => 'active-shop',
        ]);

        $response = $this->getJson("/api/v1/vendors/{$vendor->slug}");

        $response->assertOk()
            ->assertJsonPath('data.shop_name', 'Active Shop');
    }

    // ─── Vendor Registration (Authenticated) ──────────────────────

    /** @test */
    public function authenticated_user_can_register_as_vendor(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/vendor/register', [
                'shop_name'   => 'My New Shop',
                'description' => 'A test shop',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.shop_name', 'My New Shop')
            ->assertJsonPath('data.status', 'pending');
    }

    /** @test */
    public function vendor_cannot_register_twice(): void
    {
        Vendor::factory()->create([
            'user_id'   => $this->user->id,
            'shop_name' => 'First Shop',
            'slug'      => 'first-shop',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/vendor/register', [
                'shop_name' => 'Second Shop',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function unauthenticated_user_cannot_register_as_vendor(): void
    {
        $response = $this->postJson('/api/v1/vendor/register', [
            'shop_name' => 'Unauthorized Shop',
        ]);

        $response->assertStatus(401);
    }

    // ─── Vendor Profile (Authenticated) ───────────────────────────

    /** @test */
    public function vendor_can_view_own_profile(): void
    {
        Vendor::factory()->create([
            'user_id'   => $this->user->id,
            'shop_name' => 'My Shop',
            'slug'      => 'my-shop',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/vendor/profile');

        $response->assertOk()
            ->assertJsonPath('data.shop_name', 'My Shop');
    }

    /** @test */
    public function non_vendor_gets_404_on_profile(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/vendor/profile');

        $response->assertStatus(404);
    }

    /** @test */
    public function vendor_can_update_own_profile(): void
    {
        Vendor::factory()->create([
            'user_id'   => $this->user->id,
            'shop_name' => 'My Shop',
            'slug'      => 'my-shop',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/vendor/profile', [
                'description' => 'Updated description',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.description', 'Updated description');
    }

    /** @test */
    public function vendor_can_update_profile_without_changing_shop_name(): void
    {
        Vendor::factory()->create([
            'user_id'   => $this->user->id,
            'shop_name' => 'My Shop',
            'slug'      => 'my-shop',
        ]);

        // Sending same shop_name should not trigger uniqueness error
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/vendor/profile', [
                'shop_name'   => 'My Shop',
                'description' => 'Updated description',
            ]);

        $response->assertOk();
    }

    // ─── Document Upload ──────────────────────────────────────────

    /** @test */
    public function vendor_can_upload_document(): void
    {
        Vendor::factory()->create([
            'user_id'   => $this->user->id,
            'shop_name' => 'My Shop',
            'slug'      => 'my-shop',
        ]);

        // Use a fake file upload
        $file = \Illuminate\Http\UploadedFile::fake()->image('license.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/vendor/documents', [
                'type'     => 'trade_license',
                'document' => $file,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'trade_license');
    }

    /** @test */
    public function vendor_cannot_upload_without_document_file(): void
    {
        Vendor::factory()->create([
            'user_id'   => $this->user->id,
            'shop_name' => 'My Shop',
            'slug'      => 'my-shop',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/vendor/documents', [
                'type' => 'nid',
                // No document file
            ]);

        $response->assertStatus(422);
    }

    // ─── Admin Vendor Routes (requires seeded permissions in production) ──
    // Note: Admin route tests (approve/reject/suspend) are intentionally
    // omitted here because they require pre-seeded admin roles and permissions
    // via Spatie. In a real test environment, seed the RBAC seeder first in setUp().
    // See: Modules/RBAC/database/seeders/RBACSeeder.php
}
