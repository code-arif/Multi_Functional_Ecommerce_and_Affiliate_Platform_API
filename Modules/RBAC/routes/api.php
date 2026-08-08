<?php

use Illuminate\Support\Facades\Route;
use Modules\RBAC\Http\Controllers\PermissionController;
use Modules\RBAC\Http\Controllers\RoleController;
use Modules\RBAC\Http\Controllers\UserRoleController;

/*
|--------------------------------------------------------------------------
| RBAC Module API Routes
|
| Prefix: api/v1/admin/rbac
| Middleware: auth:sanctum, admin, banned
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'v1/admin', 'middleware' => ['auth:sanctum', 'admin']], function () {

    Route::group(['prefix' => 'rbac'], function () {

        // Roles
        Route::group(['prefix' => 'roles'], function () {
            Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles.view'); // DONE: Role list
            Route::post('/store', [RoleController::class, 'store'])->middleware('permission:roles.manage'); // DONE: Role create
            Route::get('/{id}/show', [RoleController::class, 'show'])->middleware('permission:roles.view'); 
            Route::put('/{id}/update', [RoleController::class, 'update'])->middleware('permission:roles.manage');  // DONE: Update role
            Route::delete('/{id}/destroy', [RoleController::class, 'destroy'])->middleware('permission:roles.manage'); // DONE: Delete role
            Route::post('/{id}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:roles.manage'); // DONE: Sync role permissions
        });

        // Permissions
        // NOTE: literal routes (groups/grouped) MUST come before parameterized ({id})
        Route::group(['prefix' => 'permissions'], function () {
            Route::get('/', [PermissionController::class, 'index'])->middleware('permission:permissions.view');                    // DONE: Permission list
            Route::post('/store', [PermissionController::class, 'store'])->middleware('permission:permissions.manage');            // DONE: Permission create
            Route::get('/groups', [PermissionController::class, 'groups'])->middleware('permission:permissions.view');             // DONE: Permission groups list
            Route::get('/grouped', [PermissionController::class, 'grouped'])->middleware('permission:permissions.view');           // DONE: Group wise permission list
            Route::get('/{id}', [PermissionController::class, 'show'])->middleware('permission:permissions.view');                 // DONE: Get permission with associated roles
            Route::put('/{id}/update', [PermissionController::class, 'update'])->middleware('permission:permissions.manage');      // DONE: Update permission
            Route::delete('/{id}/destroy', [PermissionController::class, 'destroy'])->middleware('permission:permissions.manage'); // DONE: Delete permission
        });

        // User-Role Assignments
        Route::get('users', [UserRoleController::class, 'index'])->middleware('permission:users.view');
        Route::post('users/assign', [UserRoleController::class, 'assign'])->middleware('permission:roles.manage');
        Route::get('users/{userId}/permissions', [UserRoleController::class, 'userPermissions'])->middleware('permission:users.view');
    });

    // Authenticated user route (outside admin prefix)
    Route::middleware('auth:sanctum')->get('auth/my-permissions', [UserRoleController::class, 'myRoles']);
});
