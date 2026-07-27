<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\AdminPanel\Models\Setting;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();
        return $this->successResponse($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key'   => 'required|string|max:100',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'] ?? '']
            );
        }

        return $this->successResponse(null, 'Settings updated.');
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,svg,pdf|max:5120',
        ]);

        $path = $request->file('file')->store('settings', 'public');

        return $this->successResponse([
            'path' => $path,
            'url'  => asset('storage/' . $path),
        ], 'File uploaded.');
    }
}
