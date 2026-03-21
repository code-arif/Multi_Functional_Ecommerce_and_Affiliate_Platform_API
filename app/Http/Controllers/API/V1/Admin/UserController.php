<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;
    
    public function index(Request $request): JsonResponse
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return $this->paginatedResponse(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['roles', 'orders', 'addresses']);
        return $this->successResponse(new UserResource($user));
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $request->validate(['status' => 'required|in:active,inactive,banned']);
        $user->update(['status' => $request->status]);
        return $this->successResponse(null, "User status updated to {$request->status}.");
    }
}
