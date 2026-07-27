<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Models\ActivityLog;
use Modules\Core\Http\Resources\ActivityLogResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::with('user')
            ->when($request->module, fn($q, $m) => $q->where('module', $m))
            ->when($request->action, fn($q, $a) => $q->where('action', $a))
            ->when($request->user_id, fn($q, $id) => $q->where('user_id', $id))
            ->when($request->from, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse(ActivityLogResource::collection($logs));
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load('user');
        return $this->successResponse(new ActivityLogResource($activityLog));
    }
}
