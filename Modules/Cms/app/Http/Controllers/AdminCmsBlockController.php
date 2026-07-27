<?php

namespace Modules\Cms\Http\Controllers;

use Modules\Cms\Services\CmsService;
use Modules\Cms\Models\CmsBlock;
use Modules\Cms\Http\Resources\CmsBlockResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCmsBlockController
{
    use ApiResponse;

    public function __construct(private CmsService $cmsService) {}

    public function index(Request $request): JsonResponse
    {
        $query = CmsBlock::query();
        if ($request->type) $query->where('type', $request->type);
        return $this->paginatedResponse(CmsBlockResource::collection($query->ordered()->paginate(20)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'slug'      => 'nullable|string|max:255|unique:cms_blocks,slug',
            'type'      => 'required|in:html,markdown,image,slider',
            'content'   => 'nullable|string',
            'data'      => 'nullable|array',
            'is_active' => 'boolean',
            'order'     => 'nullable|integer|min:0',
        ]);

        $block = $this->cmsService->createBlock($validated);
        return $this->createdResponse(new CmsBlockResource($block), 'Block created.');
    }

    public function show(CmsBlock $block): JsonResponse
    {
        return $this->successResponse(new CmsBlockResource($block));
    }

    public function update(Request $request, CmsBlock $block): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'slug'      => ['nullable', 'string', 'max:255', Rule::unique('cms_blocks')->ignore($block->id)],
            'type'      => 'sometimes|in:html,markdown,image,slider',
            'content'   => 'nullable|string',
            'data'      => 'nullable|array',
            'is_active' => 'boolean',
            'order'     => 'nullable|integer|min:0',
        ]);

        $block = $this->cmsService->updateBlock($block, $validated);
        return $this->successResponse(new CmsBlockResource($block), 'Block updated.');
    }

    public function destroy(CmsBlock $block): JsonResponse
    {
        $this->cmsService->deleteBlock($block);
        return $this->successResponse(null, 'Block deleted.');
    }
}
