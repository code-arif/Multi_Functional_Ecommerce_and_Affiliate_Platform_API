<?php

namespace Modules\RBAC\Http\Controllers;

use Modules\RBAC\Services\RBACService;
use Modules\RBAC\Http\Resources\RoleResource;
use Modules\RBAC\Http\Requests\AssignRoleRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserRoleController
{
    use ApiResponse;

    public function __construct(private RBACService $rbacService) {}

    /**
     * List users with their roles, with optional role filter.
     */
    public function index(Request $request): JsonResponse
    {
        $users = $this->rbacService->listUsersWithRoles($request->only([
            'search', 'role', 'sort', 'order', 'per_page'
        ]));

        return $this->paginatedResponse($users);
    }

    /**
     * Assign (sync) roles to a user.
     */
    public function assign(AssignRoleRequest $request): JsonResponse
    {
        $user = $this->rbacService->assignRolesToUser(
            \App\Models\User::findByUuidOrFail($request->validated('user_uuid'))->id,
            $request->validated('roles')
        );

        return $this->successResponse([
            'user'  => [
                'uuid'  => $user->uuid,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->roles->pluck('name'),
        ], 'Roles assigned successfully.');
    }

    /**
     * Get all permissions for a specific user.
     */
    public function userPermissions(string $userId): JsonResponse
    {
        $permissions = $this->rbacService->getUserPermissions(
            \App\Models\User::findByUuidOrFail($userId)->id
        );
        return $this->successResponse($permissions->pluck('name'));
    }

    /**
     * Get roles of the currently authenticated user.
     */
    public function myRoles(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');
        return $this->successResponse([
            'roles'       => $user->roles->pluck('name'),
            'permissions' => $user->roles
                ->flatMap(fn($r) => $r->permissions)
                ->pluck('name')
                ->unique()
                ->values(),
        ]);
    }
}
