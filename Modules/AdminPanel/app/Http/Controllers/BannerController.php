<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Promotions\Models\Banner;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $banners = Banner::orderBy('position')->orderBy('sort_order')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse($banners);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:200',
            'subtitle'     => 'nullable|string|max:500',
            'description'  => 'nullable|string',
            'image'        => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'mobile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'link'         => 'nullable|url|max:500',
            'position'     => 'required|string|max:50',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'expires_at'   => 'nullable|date|after:starts_at',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('banners', 'public');
        }
        if ($request->hasFile('mobile_image')) {
            $validated['mobile_image'] = $request->file('mobile_image')->store('banners/mobile', 'public');
        }

        $banner = Banner::create($validated);

        return $this->createdResponse($banner, 'Banner created.');
    }

    public function update(Banner $banner, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'sometimes|string|max:200',
            'subtitle'     => 'nullable|string|max:500',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'mobile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'link'         => 'nullable|url|max:500',
            'position'     => 'sometimes|string|max:50',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'expires_at'   => 'nullable|date|after:starts_at',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('banners', 'public');
        }
        if ($request->hasFile('mobile_image')) {
            $validated['mobile_image'] = $request->file('mobile_image')->store('banners/mobile', 'public');
        }

        $banner->update($validated);

        return $this->successResponse($banner->fresh(), 'Banner updated.');
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();
        return $this->noContentResponse('Banner deleted.');
    }
}
