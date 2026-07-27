<?php

namespace Modules\Auth\Services;

use Modules\Auth\Models\Device;
use Modules\Auth\Models\User;
use Illuminate\Http\Request;

class DeviceService
{
    public function registerDevice(User $user, Request $request, string $deviceName = 'api'): Device
    {
        $device = Device::updateOrCreate(
            [
                'user_id'    => $user->id,
                'device_name' => $this->detectDeviceName($request, $deviceName),
            ],
            [
                'device_type'    => $this->detectDeviceType($request),
                'platform'       => $this->detectPlatform($request),
                'browser'        => $this->detectBrowser($request),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'last_active_at' => now(),
            ]
        );

        return $device;
    }

    public function getUserDevices(User $user)
    {
        return Device::where('user_id', $user->id)
            ->orderByDesc('last_active_at')
            ->get();
    }

    public function revokeDevice(User $user, int $deviceId): bool
    {
        $device = Device::where('user_id', $user->id)
            ->where('id', $deviceId)
            ->first();

        if (!$device) {
            return false;
        }

        $device->delete();
        return true;
    }

    public function revokeAllDevices(User $user): void
    {
        Device::where('user_id', $user->id)->delete();
    }

    public function updateLastActive(Device $device): void
    {
        $device->updateLastActive();
    }

    public function trustDevice(User $user, int $deviceId): bool
    {
        $device = Device::where('user_id', $user->id)
            ->where('id', $deviceId)
            ->first();

        if (!$device) {
            return false;
        }

        $device->update(['is_trusted' => true]);
        return true;
    }

    private function detectDeviceName(Request $request, string $default): string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Mobi')) return 'Mobile';
        if (str_contains($ua, 'Tablet')) return 'Tablet';
        if (str_contains($ua, 'Postman')) return 'Postman';
        return $default;
    }

    private function detectDeviceType(Request $request): string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Mobi') || str_contains($ua, 'Android')) return 'mobile';
        if (str_contains($ua, 'Tablet') || str_contains($ua, 'iPad')) return 'tablet';
        return 'desktop';
    }

    private function detectPlatform(Request $request): ?string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Windows')) return 'windows';
        if (str_contains($ua, 'Mac OS')) return 'mac';
        if (str_contains($ua, 'Linux')) return 'linux';
        if (str_contains($ua, 'Android')) return 'android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'ios';
        return null;
    }

    private function detectBrowser(Request $request): ?string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) return 'chrome';
        if (str_contains($ua, 'Firefox')) return 'firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'safari';
        if (str_contains($ua, 'Edg')) return 'edge';
        if (str_contains($ua, 'Postman')) return 'postman';
        return null;
    }
}
