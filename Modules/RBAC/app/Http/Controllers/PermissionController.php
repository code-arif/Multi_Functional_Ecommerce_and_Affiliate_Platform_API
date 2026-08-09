<?php

namespace Modules\RBAC\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Traits\ApiResponse;
use Modules\RBAC\Http\Requests\StorePermissionRequest;
use Modules\RBAC\Http\Requests\UpdatePermissionRequest;
use Modules\RBAC\Services\RBACService;
use Modules\RBAC\Transformers\PermissionResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PermissionController
{
    use ApiResponse;

    public function __construct(private RBACService $rbacService) {}

    /**
     * List all permissions with pagination, search, and group filter.
     */
    public function index(Request $request): JsonResponse
    {
        $permissions = $this->rbacService->listPermissions($request->only([
            'search', 'group', 'guard', 'sort', 'order', 'per_page'
        ]));

        return $this->paginatedResponse(
            PermissionResource::collection($permissions)
        );
    }

    /**
     * Get a single permission with its assigned roles.
     */
    public function show(int $id): JsonResponse
    {
        $permission = $this->rbacService->getPermission($id);
        return $this->successResponse(new PermissionResource($permission));
    }

    /**
     * Create a new permission.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->rbacService->createPermission($request->validated());
        return $this->createdResponse(
            new PermissionResource($permission),
            'Permission created successfully.'
        );
    }

    /**
     * Update a permission.
     */
    public function update(UpdatePermissionRequest $request, int $id): JsonResponse
    {
        $permission = $this->rbacService->updatePermission($id, $request->validated());
        return $this->successResponse(
            new PermissionResource($permission),
            'Permission updated successfully.'
        );
    }

    /**
     * Delete a permission (only if not assigned to any role).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->rbacService->deletePermission($id);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Permission not found.');
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), null, $e->getStatusCode());
        }

        return $this->noContentResponse('Permission deleted successfully.');
    }

    /**
     * List all permission groups.
     */
    public function groups(): JsonResponse
    {
        $groups = $this->rbacService->getAllPermissionGroups();
        return $this->successResponse($groups);
    }

    /**
     * List all permissions grouped by their group name.
     */
    public function grouped(): JsonResponse
    {
        $grouped = $this->rbacService->getPermissionsByGroup();
        $result = $grouped->map(function ($permissions, $group) {
            return [
                'group' => $group,
                'permissions' => PermissionResource::collection($permissions),
            ];
        })->values();

        return $this->successResponse($result);
    }
}
