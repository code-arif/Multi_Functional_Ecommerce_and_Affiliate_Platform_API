<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\AdminPanel\Models\Setting;
use Modules\AdminPanel\Http\Resources\SettingResource;
use Modules\AdminPanel\Http\Requests\UpdateSettingsRequest;
use Modules\AdminPanel\Http\Requests\UploadSettingFileRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class SettingController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();
        return $this->successResponse(SettingResource::collection($settings));
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        foreach ($request->validated('settings') as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'] ?? '']
            );
        }

        return $this->successResponse(null, 'Settings updated.');
    }

    public function uploadFile(UploadSettingFileRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('settings', 'public');

        return $this->successResponse([
            'path' => $path,
            'url'  => asset('storage/' . $path),
        ], 'File uploaded.');
    }
}
