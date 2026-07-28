<?php

namespace Modules\RBAC\Policies;

use Modules\Auth\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        // Prevent modifying super-admin role
        if ($role->name === 'super-admin' && !$user->hasRole('super-admin')) {
            return false;
        }

        return $user->hasPermissionTo('roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        // Prevent deleting system roles
        if (in_array($role->name, ['super-admin', 'admin', 'customer'])) {
            return false;
        }

        return $user->hasPermissionTo('roles.manage');
    }

    public function syncPermissions(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('roles.manage');
    }
}
