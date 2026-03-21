<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    use ApiResponse;
    
    public function index(): JsonResponse
    {
        $pages = CmsPage::withTrashed()->orderBy('sort_order')->get();
        return $this->successResponse($pages);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'content'          => 'nullable|string',
            'template'         => 'nullable|string|max:50',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active'        => 'boolean',
        ]);
        $page = CmsPage::create(array_merge($request->validated(), ['created_by' => auth()->id()]));
        return $this->createdResponse($page, 'Page created.');
    }

    public function update(Request $request, CmsPage $cmsPage): JsonResponse
    {
        $cmsPage->update($request->except('slug'));
        return $this->successResponse($cmsPage, 'Page updated.');
    }

    public function destroy(CmsPage $cmsPage): JsonResponse
    {
        $cmsPage->delete();
        return $this->noContentResponse('Page deleted.');
    }
}
