<?php

namespace Modules\RBAC\Services;

use \Exception;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RBACService
{
    // Roles list
    public function listRoles(array $filters = []): LengthAwarePaginator
    {
        return Role::query()
            ->withCount('permissions')
            ->when($filters['search'] ?? null, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('display_name', 'like', "%{$s}%")
            )
            ->when($filters['guard'] ?? null, fn($q, $g) => $q->where('guard_name', $g))
            ->orderBy($filters['sort'] ?? 'name', $filters['order'] ?? 'asc')
            ->paginate($filters['per_page'] ?? 50);
    }

    public function getRole(int $id): Role
    {
        $role = Role::with('permissions')->withCount('users')->find($id);

        if (!$role) {
            throw new Exception("Role with ID {$id} not found.");
        }
        return $role;
    }

    public function createRole(array $data): Role
    {
        $roleData = [
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'api',
        ];

        if (isset($data['display_name'])) {
            $roleData['display_name'] = $data['display_name'];
        }
        if (isset($data['description'])) {
            $roleData['description'] = $data['description'];
        }

        $role = Role::create($roleData);

        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->fresh()->load('permissions');
    }

    public function updateRole(int $id, array $data): Role
    {
        $role = Role::findOrFail($id);

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('display_name', $data)) {
            $updateData['display_name'] = $data['display_name'];
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }
        if (isset($data['guard_name'])) {
            $updateData['guard_name'] = $data['guard_name'];
        }

        if (!empty($updateData)) {
            $role->update($updateData);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->fresh()->load('permissions');
    }

    public function deleteRole(int $id): bool
    {
        $role = Role::findOrFail($id);

        // Prevent deletion of critical system roles
        if (in_array($role->name, ['super-admin', 'admin', 'customer'])) {
            abort(422, "The '{$role->name}' role is a system role and cannot be deleted.");
        }

        return $role->delete();
    }

    public function syncRolePermissions(int $roleId, array $permissions): Role
    {
        $role = Role::findOrFail($roleId);
        $role->syncPermissions($permissions);
        return $role->fresh()->load('permissions');
    }

    // Permission List
    public function listPermissions(array $filters = []): LengthAwarePaginator
    {
        return Permission::query()
            ->withCount('roles')
            ->when($filters['search'] ?? null, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('display_name', 'like', "%{$s}%")
            )
            ->when($filters['group'] ?? null, fn($q, $g) => $q->where('group', $g))
            ->when($filters['guard'] ?? null, fn($q, $g) => $q->where('guard_name', $g))
            ->orderBy($filters['sort'] ?? 'group', $filters['order'] ?? 'asc')
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 50);
    }

    // Get permission with associated roles
    public function getPermission(int $id): Permission
    {
        return Permission::with('roles')->findOrFail($id);
    }

    // Store Permission
    public function createPermission(array $data): Permission
    {
        $permData = [
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'api',
        ];

        if (isset($data['display_name'])) {
            $permData['display_name'] = $data['display_name'];
        }
        if (isset($data['group'])) {
            $permData['group'] = $data['group'];
        }

        return Permission::create($permData);
    }

    // Update Permission
    public function updatePermission(int $id, array $data): Permission
    {
        $permission = Permission::findOrFail($id);

        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (array_key_exists('display_name', $data)) {
            $updateData['display_name'] = $data['display_name'];
        }
        if (array_key_exists('group', $data)) {
            $updateData['group'] = $data['group'];
        }
        if (isset($data['guard_name'])) {
            $updateData['guard_name'] = $data['guard_name'];
        }

        if (!empty($updateData)) {
            $permission->update($updateData);
        }

        return $permission->fresh();
    }

    // Delete Permission
    public function deletePermission(int $id): bool
    {
        $permission = Permission::findOrFail($id);

        // Prevent deletion of permissions that are in use
        if ($permission->roles()->count() > 0) {
            throw new Exception('This permission is assigned to one or more roles and cannot be deleted. Remove it from all roles first.');
        }

        return $permission->delete();
    }

    // Permission Groups List
    public function getAllPermissionGroups(): Collection
    {
        return Permission::query()
            ->select('group')
            ->whereNotNull('group')
            ->where('group', '!=', '')
            ->distinct()
            ->pluck('group')
            ->values();
    }

    //Group wise permission list
    public function getPermissionsByGroup(): Collection
    {
        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        return $permissions->groupBy(fn($p) => $p->group ?: 'Uncategorized');
    }

    // ─── User-Role Management ────────────────────────────────────

    public function assignRolesToUser(int $userId, array $roleNames): User
    {
        $user = User::findOrFail($userId);
        $user->syncRoles($roleNames);
        return $user->load('roles');
    }

    public function getUserPermissions(int $userId): Collection
    {
        $user = User::findOrFail($userId);
        return $user->getAllPermissions();
    }

    public function listUsersWithRoles(array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($filters['search'] ?? null, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
            )
            ->when($filters['role'] ?? null, fn($q, $r) =>
                $q->whereHas('roles', fn($qr) => $qr->where('name', $r))
            )
            ->orderBy($filters['sort'] ?? 'created_at', $filters['order'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }
}
