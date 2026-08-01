<?php

namespace Modules\Auth\Http\Controllers;

use Modules\Auth\Services\DeviceService;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController
{
    use ApiResponse;

    public function __construct(private DeviceService $deviceService) {}

    /**
     * GET /api/v1/auth/devices
     * List all devices/sessions for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $devices = $this->deviceService->getUserDevices($request->user());
        return $this->successResponse($devices);
    }

    /**
     * DELETE /api/v1/auth/devices/{device}
     * Revoke a specific device/session
     */
    public function destroy(string $device, Request $request): JsonResponse
    {
        $revoked = $this->deviceService->revokeDevice($request->user(), $device);

        if (!$revoked) {
            return $this->errorResponse('Device not found.', null, 404);
        }

        return $this->noContentResponse('Device session revoked.');
    }

    /**
     * DELETE /api/v1/auth/devices
     * Revoke all devices/sessions
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $this->deviceService->revokeAllDevices($request->user());
        // Also revoke all tokens
        $request->user()->tokens()->delete();

        return $this->noContentResponse('All devices revoked. Please login again.');
    }

    /**
     * POST /api/v1/auth/devices/{device}/trust
     * Mark a device as trusted
     */
    public function trust(string $device, Request $request): JsonResponse
    {
        $trusted = $this->deviceService->trustDevice($request->user(), $device);

        if (!$trusted) {
            return $this->errorResponse('Device not found.', null, 404);
        }

        return $this->successResponse(null, 'Device marked as trusted.');
    }
}
