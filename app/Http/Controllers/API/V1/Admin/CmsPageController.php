<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CMS\StoreCmsPageRequest;
use App\Models\CmsPage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $pages = CmsPage::withTrashed()->orderBy('sort_order')->get();
        return $this->successResponse($pages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCmsPageRequest $request): JsonResponse
    {
        $page = CmsPage::create($request->validatedWithExtras());

        return $this->createdResponse($page, 'Page created.');
    }

    /**
     * Display the specified resource.
     */
    public function update(Request $request, CmsPage $cmsPage): JsonResponse
    {
        $cmsPage->update($request->except('slug'));
        return $this->successResponse($cmsPage, 'Page updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CmsPage $cmsPage): JsonResponse
    {
        $cmsPage->delete();
        return $this->noContentResponse('Page deleted.');
    }
}
