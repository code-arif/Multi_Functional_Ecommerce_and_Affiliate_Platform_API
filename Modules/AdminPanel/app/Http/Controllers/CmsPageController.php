<?php

namespace Modules\AdminPanel\Http\Controllers;

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
            ->orderBy('order')
            ->paginate($request->per_page ?? 20);

        return $this->paginatedResponse($pages);
    }

    public function show(CmsPage $page): JsonResponse
    {
        return $this->successResponse($page);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:200',
            'slug'            => 'nullable|string|max:200|unique:cms_pages,slug',
            'content'         => 'nullable|string',
            'excerpt'         => 'nullable|string|max:500',
            'meta_title'      => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'   => 'nullable|string|max:255',
            'og_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'template'        => 'nullable|string|max:50',
            'is_published'    => 'boolean',
            'published_at'    => 'nullable|date',
            'order'           => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('og_image')) {
            $validated['og_image'] = $request->file('og_image')->store('cms/pages', 'public');
        }

        $page = CmsPage::create($validated);

        return $this->createdResponse($page, 'Page created.');
    }

    public function update(CmsPage $page, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'           => 'sometimes|string|max:200',
            'slug'            => 'nullable|string|max:200|unique:cms_pages,slug,' . $page->id,
            'content'         => 'nullable|string',
            'excerpt'         => 'nullable|string|max:500',
            'meta_title'      => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'meta_keywords'   => 'nullable|string|max:255',
            'og_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'template'        => 'nullable|string|max:50',
            'is_published'    => 'boolean',
            'published_at'    => 'nullable|date',
            'order'           => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('og_image')) {
            $validated['og_image'] = $request->file('og_image')->store('cms/pages', 'public');
        }

        $page->update($validated);

        return $this->successResponse($page->fresh(), 'Page updated.');
    }

    public function destroy(CmsPage $page): JsonResponse
    {
        $page->delete();
        return $this->noContentResponse('Page deleted.');
    }
}
