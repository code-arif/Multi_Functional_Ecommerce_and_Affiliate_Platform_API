<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BannerController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $banners = Banner::orderBy('position')->orderBy('sort_order')->get();
        return $this->successResponse(BannerResource::collection($banners));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'        => 'required|string|max:200',
            'subtitle'     => 'nullable|string|max:300',
            'image'        => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'mobile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'link'         => 'nullable|string|max:500',
            'button_text'  => 'nullable|string|max:50',
            'position'     => 'required|in:hero_slider,homepage_top,homepage_middle,homepage_bottom,sidebar,promotional,category_banner',
            'sort_order'   => 'nullable|integer',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date',
        ]);

        $data = $request->except(['image', 'mobile_image']);
        $data['image'] = $request->file('image')->store('banners', 'public');
        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
        }

        $banner = Banner::create($data);
        Cache::forget('homepage_data');

        return $this->createdResponse(new BannerResource($banner), 'Banner created.');
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $data = $request->except(['image', 'mobile_image']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }
        if ($request->hasFile('mobile_image')) {
            $data['mobile_image'] = $request->file('mobile_image')->store('banners', 'public');
        }
        $banner->update($data);
        Cache::forget('homepage_data');
        return $this->successResponse(new BannerResource($banner), 'Banner updated.');
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();
        Cache::forget('homepage_data');
        return $this->noContentResponse('Banner deleted.');
    }
}
