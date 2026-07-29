<?php

namespace Modules\AdminPanel\Http\Controllers;

use Modules\Cms\Http\Resources\CmsPageResource;
use Modules\Cms\Http\Requests\StoreCmsPageRequest;
use Modules\AdminPanel\Http\Requests\UpdateCmsPageRequest;
use Modules\Cms\Models\CmsPage;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsPageController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $pages = CmsPage::when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy(\DB::raw('`cms_pages`.`order`'))
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse(CmsPageResource::collection($pages));
    }

    public function show(CmsPage $page): JsonResponse
    {
        return $this->successResponse(new CmsPageResource($page));
    }

    public function store(StoreCmsPageRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('cms/pages', 'public');
        }

        $page = CmsPage::create($data);

        return $this->createdResponse(new CmsPageResource($page), 'Page created.');
    }

    public function update(CmsPage $page, UpdateCmsPageRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $request->file('og_image')->store('cms/pages', 'public');
        }

        $page->update($data);

        return $this->successResponse(new CmsPageResource($page->fresh()), 'Page updated.');
    }

    public function destroy(CmsPage $page): JsonResponse
    {
        $page->delete();
        return $this->noContentResponse('Page deleted.');
    }
}
