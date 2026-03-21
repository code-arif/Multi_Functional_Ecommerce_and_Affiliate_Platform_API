<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group');
        return $this->successResponse($settings);
    }

    public function update(Request $request): JsonResponse
    {
        foreach ($request->settings as $key => $value) {
            Setting::set($key, $value);
        }
        Cache::forget('public_settings');
        Cache::forget('homepage_data');
        return $this->successResponse(null, 'Settings updated.');
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,webp,ico|max:2048',
            'key'  => 'required|string',
        ]);
        $path = $request->file('file')->store('settings', 'public');
        Setting::set($request->key, $path);
        return $this->successResponse(['path' => $path, 'url' => asset('storage/' . $path)]);
    }
}
