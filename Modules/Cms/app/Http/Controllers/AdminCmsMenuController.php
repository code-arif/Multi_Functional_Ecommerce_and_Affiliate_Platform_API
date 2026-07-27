<?php

namespace Modules\Cms\Http\Controllers;

use Modules\Cms\Services\CmsService;
use Modules\Cms\Models\CmsMenu;
use Modules\Cms\Http\Resources\CmsMenuResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCmsMenuController
{
    use ApiResponse;

    public function __construct(private CmsService $cmsService) {}

    public function index(): JsonResponse
    {
        $menus = CmsMenu::all();
        return $this->successResponse(CmsMenuResource::collection($menus));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'nullable|string|max:255|unique:cms_menus,slug',
            'location'  => 'required|in:header,footer,sidebar,mobile',
            'items'     => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $menu = $this->cmsService->createMenu($validated);
        return $this->createdResponse(new CmsMenuResource($menu), 'Menu created.');
    }

    public function show(CmsMenu $menu): JsonResponse
    {
        return $this->successResponse(new CmsMenuResource($menu));
    }

    public function update(Request $request, CmsMenu $menu): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'slug'      => ['nullable', 'string', 'max:255', Rule::unique('cms_menus')->ignore($menu->id)],
            'location'  => 'sometimes|in:header,footer,sidebar,mobile',
            'items'     => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $menu = $this->cmsService->updateMenu($menu, $validated);
        return $this->successResponse(new CmsMenuResource($menu), 'Menu updated.');
    }

    public function destroy(CmsMenu $menu): JsonResponse
    {
        $this->cmsService->deleteMenu($menu);
        return $this->successResponse(null, 'Menu deleted.');
    }
}
