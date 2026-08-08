<?php

namespace Modules\RBAC\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\RBAC\Http\Requests\StoreRoleRequest;
use Modules\RBAC\Http\Requests\SyncRolePermissionsRequest;
use Modules\RBAC\Http\Requests\UpdateRoleRequest;
use Modules\RBAC\Services\RBACService;
use Modules\RBAC\Transformers\RoleListResource;
use Modules\RBAC\Transformers\RoleResource;

class RoleController
{
    use ApiResponse;

    public function __construct(private RBACService $rbacService) {}

    /**
     * List all roles with pagination and search.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = $this->rbacService->listRoles($request->only([
            'search', 'guard', 'sort', 'order', 'per_page'
        ]));

        return $this->paginatedResponse(
            RoleListResource::collection($roles)
        );
    }

    /**
     * Get a single role with its permissions.
     */
    public function show(int $id): JsonResponse
    {
        $role = $this->rbacService->getRole($id);
        return $this->successResponse(new RoleResource($role));
    }

    /**
     * Create a new role with optional permissions.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->rbacService->createRole($request->validated());
        return $this->createdResponse(
            new RoleResource($role),
            'Role created successfully.'
        );
    }

    /**
     * Update a role's details and/or permissions.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = $this->rbacService->updateRole($id, $request->validated());
        return $this->successResponse(
            new RoleResource($role),
            'Role updated successfully.'
        );
    }

    /**
     * Delete a role (system roles cannot be deleted).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->rbacService->deleteRole($id);
        return $this->noContentResponse('Role deleted successfully.');
    }

    /**
     * Sync permissions to a role.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, int $id): JsonResponse
    {
        $role = $this->rbacService->syncRolePermissions($id, $request->validated('permissions'));
        return $this->successResponse(
            new RoleResource($role),
            'Permissions synced successfully.'
        );
    }
}
