<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\AdminPanel\Http\Requests\UpdateUserStatusRequest;
use App\Models\User;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->role, fn($q) => $q->whereHas('roles', fn($q) => $q->where('name', $request->role)))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles', 'orders');
        return $this->successResponse(new UserResource($user));
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $status = $request->validated('status');
        $user->update(['status' => $status]);

        if ($status === 'banned') {
            $user->tokens()->delete();
        }

        return $this->successResponse(new UserResource($user->fresh()), "User status updated to {$status}.");
    }
}
