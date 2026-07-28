<?php

use Illuminate\Support\Facades\Route;
use Modules\RBAC\Http\Controllers\RoleController;
use Modules\RBAC\Http\Controllers\PermissionController;
use Modules\RBAC\Http\Controllers\UserRoleController;

/*
|--------------------------------------------------------------------------
| RBAC Module API Routes
|
| Prefix: api/v1/admin/rbac
| Middleware: auth:sanctum, admin, banned
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin', 'banned'])
    ->prefix('admin/rbac')
    ->group(function () {

        // ─── Roles ───────────────────────────────────────────────
        Route::get('roles',                     [RoleController::class, 'index'])
            ->middleware('permission:roles.view');
        Route::post('roles',                    [RoleController::class, 'store'])
            ->middleware('permission:roles.manage');
        Route::get('roles/{id}',                [RoleController::class, 'show'])
            ->middleware('permission:roles.view');
        Route::put('roles/{id}',                [RoleController::class, 'update'])
            ->middleware('permission:roles.manage');
        Route::delete('roles/{id}',             [RoleController::class, 'destroy'])
            ->middleware('permission:roles.manage');
        Route::post('roles/{id}/permissions',   [RoleController::class, 'syncPermissions'])
            ->middleware('permission:roles.manage');

        // ─── Permissions ─────────────────────────────────────────
        // NOTE: literal routes (groups/grouped) MUST come before parameterized ({id})
        Route::get('permissions',               [PermissionController::class, 'index'])
            ->middleware('permission:permissions.view');
        Route::post('permissions',              [PermissionController::class, 'store'])
            ->middleware('permission:permissions.manage');
        Route::get('permissions/groups',        [PermissionController::class, 'groups'])
            ->middleware('permission:permissions.view');
        Route::get('permissions/grouped',       [PermissionController::class, 'grouped'])
            ->middleware('permission:permissions.view');
        Route::get('permissions/{id}',          [PermissionController::class, 'show'])
            ->middleware('permission:permissions.view');
        Route::put('permissions/{id}',          [PermissionController::class, 'update'])
            ->middleware('permission:permissions.manage');
        Route::delete('permissions/{id}',       [PermissionController::class, 'destroy'])
            ->middleware('permission:permissions.manage');

        // ─── User-Role Assignments ────────────────────────────────
        Route::get('users',                     [UserRoleController::class, 'index'])
            ->middleware('permission:users.view');
        Route::post('users/assign',             [UserRoleController::class, 'assign'])
            ->middleware('permission:roles.manage');
        Route::get('users/{userId}/permissions',[UserRoleController::class, 'userPermissions'])
            ->middleware('permission:users.view');
    });

// ─── Authenticated user route (outside admin prefix) ──────────────
Route::middleware('auth:sanctum')->get('auth/my-permissions', [UserRoleController::class, 'myRoles']);
