<?php

use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(\Tests\TestCase::class)->use(DatabaseTransactions::class);

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

if (!function_exists('createRegularUser')) {
    function createRegularUser(): User
    {
        return User::create([
            'name'     => 'Regular User',
            'email'    => 'user-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status'   => 'active',
        ]);
    }
}

beforeEach(function () {
    $this->seed(\Modules\RBAC\Database\Seeders\RBACSeeder::class);
});

// ═══════════════════════════════════════════════════════════════════
// ROLE ENDPOINTS
// ═══════════════════════════════════════════════════════════════════

describe('GET /api/v1/admin/rbac/roles', function () {

    it('lists all roles for admin', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'data', 'pagination',
            ])
            ->assertJsonFragment(['name' => 'super-admin']);
    });

    it('denies access for non-admin users', function () {
        $user = createRegularUser();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/rbac/roles');

        $response->assertForbidden();
    });

    it('denies access for guests', function () {
        $response = $this->getJson('/api/v1/admin/rbac/roles');
        $response->assertUnauthorized();
    });
});

describe('POST /api/v1/admin/rbac/roles', function () {

    it('creates a new role', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/roles', [
                'name'         => 'test-role',
                'display_name' => 'Test Role',
                'description'  => 'A role for testing',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'test-role']);
        $this->assertDatabaseHas('roles', ['name' => 'test-role']);
    });

    it('creates a role with permissions', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/roles', [
                'name'        => 'limited-role',
                'permissions' => ['products.view', 'orders.view'],
            ]);

        $response->assertCreated();
        $role = Role::where('name', 'limited-role')->first();
        expect($role->hasPermissionTo('products.view'))->toBeTrue();
        expect($role->hasPermissionTo('orders.view'))->toBeTrue();
    });

    it('validates required fields', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/roles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('rejects duplicate role names', function () {
        $admin = createAdminUser();
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/roles', ['name' => 'duplicate']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/roles', ['name' => 'duplicate']);

        $response->assertStatus(422);
    });

    it('denies access without roles.manage permission', function () {
        $user = User::create([
            'name'     => 'Customer User',
            'email'    => 'customer-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status'   => 'active',
        ]);
        $user->assignRole('customer');
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/rbac/roles', ['name' => 'no-perm-role']);

        $response->assertForbidden();
    });
});

describe('GET /api/v1/admin/rbac/roles/{id}', function () {

    it('shows a role with its permissions', function () {
        $admin = createAdminUser();
        $role = Role::where('name', 'moderator')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/rbac/roles/{$role->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'moderator')
            ->assertJsonStructure(['data' => ['permissions', 'users_count']]);
    });

    it('returns 404 for non-existent role', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/roles/99999');

        $response->assertNotFound();
    });
});

describe('PUT /api/v1/admin/rbac/roles/{id}', function () {

    it('updates a role name and permissions', function () {
        $admin = createAdminUser();
        $role = Role::create(['name' => 'updatable-role', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/rbac/roles/{$role->id}", [
                'display_name' => 'Updated Role',
                'permissions'  => ['products.view', 'categories.view'],
            ]);

        $response->assertOk()
            ->assertJsonFragment(['display_name' => 'Updated Role']);
        $role->refresh();
        expect($role->hasPermissionTo('products.view'))->toBeTrue();
    });

    it('rejects invalid permission names', function () {
        $admin = createAdminUser();
        $role = Role::create(['name' => 'another-role', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/rbac/roles/{$role->id}", [
                'permissions' => ['non-existent-permission'],
            ]);

        $response->assertStatus(422);
    });
});

describe('DELETE /api/v1/admin/rbac/roles/{id}', function () {

    it('deletes a non-system role', function () {
        $admin = createAdminUser();
        $role = Role::create(['name' => 'deletable-role', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/rbac/roles/{$role->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    });

    it('prevents deleting super-admin role', function () {
        $admin = createAdminUser();
        $role = Role::where('name', 'super-admin')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/rbac/roles/{$role->id}");

        $response->assertStatus(422);
    });

    it('prevents deleting customer role', function () {
        $admin = createAdminUser();
        $role = Role::where('name', 'customer')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/rbac/roles/{$role->id}");

        $response->assertStatus(422);
    });
});

describe('POST /api/v1/admin/rbac/roles/{id}/permissions', function () {

    it('syncs permissions to a role', function () {
        $admin = createAdminUser();
        $role = Role::create(['name' => 'sync-test-role', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/rbac/roles/{$role->id}/permissions", [
                'permissions' => ['products.view', 'orders.view', 'reports.view'],
            ]);

        $response->assertOk();
        $role->refresh();
        expect($role->hasPermissionTo('products.view'))->toBeTrue();
        expect($role->hasPermissionTo('orders.view'))->toBeTrue();
        expect($role->hasPermissionTo('reports.view'))->toBeTrue();
    });

    it('validates permissions array', function () {
        $admin = createAdminUser();
        $role = Role::create(['name' => 'sync-test-role-2', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/rbac/roles/{$role->id}/permissions", []);

        $response->assertStatus(422);
    });
});

// ═══════════════════════════════════════════════════════════════════
// PERMISSION ENDPOINTS
// ═══════════════════════════════════════════════════════════════════

describe('GET /api/v1/admin/rbac/permissions', function () {

    it('lists permissions with pagination', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/permissions');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'pagination']);
    });

    it('filters permissions by group', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/permissions?group=Products');

        // Since the seeder doesn't assign groups, this might be empty
        // but should still return a valid response
        $response->assertOk();
    });
});

describe('POST /api/v1/admin/rbac/permissions', function () {

    it('creates a new permission', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/permissions', [
                'name'         => 'test.permission',
                'display_name' => 'Test Permission',
                'group'        => 'Testing',
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'test.permission']);
        $this->assertDatabaseHas('permissions', ['name' => 'test.permission']);
    });

    it('validates required fields', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/permissions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });
});

describe('PUT /api/v1/admin/rbac/permissions/{id}', function () {

    it('updates a permission', function () {
        $admin = createAdminUser();
        $perm = Permission::create(['name' => 'update.test', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/rbac/permissions/{$perm->id}", [
                'display_name' => 'Updated Permission',
                'group'        => 'Testing',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['display_name' => 'Updated Permission']);
    });
});

describe('DELETE /api/v1/admin/rbac/permissions/{id}', function () {

    it('deletes an unused permission', function () {
        $admin = createAdminUser();
        $perm = Permission::create(['name' => 'delete.test', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/rbac/permissions/{$perm->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('permissions', ['id' => $perm->id]);
    });

    it('prevents deleting a permission that is assigned to a role', function () {
        $admin = createAdminUser();
        $perm = Permission::findByName('products.view');

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/rbac/permissions/{$perm->id}");

        $response->assertStatus(422);
    });
});

describe('GET /api/v1/admin/rbac/permissions/groups', function () {

    it('lists permission groups', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/permissions/groups');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });
});

describe('GET /api/v1/admin/rbac/permissions/grouped', function () {

    it('lists permissions grouped by name', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/permissions/grouped');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });
});

// ═══════════════════════════════════════════════════════════════════
// USER-ROLE ASSIGNMENT ENDPOINTS
// ═══════════════════════════════════════════════════════════════════

describe('GET /api/v1/admin/rbac/users', function () {

    it('lists users with their roles', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/users');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'pagination']);
    });

    it('filters users by role', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/rbac/users?role=super-admin');

        $response->assertOk();
    });
});

describe('POST /api/v1/admin/rbac/users/assign', function () {

    it('assigns roles to a user', function () {
        $admin = createAdminUser();
        $targetUser = createRegularUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/users/assign', [
                'user_uuid' => $targetUser->uuid,
                'roles'   => ['moderator', 'vendor'],
            ]);

        $response->assertOk();
        $targetUser->refresh();
        expect($targetUser->hasRole('moderator'))->toBeTrue();
        expect($targetUser->hasRole('vendor'))->toBeTrue();
    });

    it('validates user existence', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/users/assign', [
                'user_uuid' => '00000000-0000-0000-0000-000000000000',
                'roles'   => ['moderator'],
            ]);

        $response->assertStatus(422);
    });

    it('validates role existence', function () {
        $admin = createAdminUser();
        $targetUser = createRegularUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/rbac/users/assign', [
                'user_uuid' => $targetUser->uuid,
                'roles'   => ['non-existent-role'],
            ]);

        $response->assertStatus(422);
    });
});

describe('GET /api/v1/admin/rbac/users/{userId}/permissions', function () {

    it('returns permissions for a specific user', function () {
        $admin = createAdminUser();
        $targetUser = createRegularUser();
        $targetUser->assignRole('moderator');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/rbac/users/{$targetUser->uuid}/permissions");

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    });
});

// ═══════════════════════════════════════════════════════════════════
// AUTHENTICATED USER PERMISSIONS
// ═══════════════════════════════════════════════════════════════════

describe('GET /api/v1/auth/my-permissions', function () {

    it('returns current user roles and permissions', function () {
        $admin = createAdminUser();
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/auth/my-permissions');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['roles', 'permissions']])
            ->assertJsonFragment(['roles' => ['super-admin']]);
    });

    it('denies access for guests', function () {
        $response = $this->getJson('/api/v1/auth/my-permissions');
        $response->assertUnauthorized();
    });
});
