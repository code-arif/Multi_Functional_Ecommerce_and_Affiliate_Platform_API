<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Models\Language;
use Modules\Core\Http\Resources\LanguageResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $languages = Language::active()->orderBy('name')->get();
        return $this->successResponse(LanguageResource::collection($languages));
    }

    public function show(Language $language): JsonResponse
    {
        return $this->successResponse(new LanguageResource($language));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $languages = Language::orderBy('name')
            ->paginate($request->per_page ?? 50);

        return $this->paginatedResponse(LanguageResource::collection($languages));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:80',
            'code'      => 'required|string|max:10|unique:languages,code',
            'direction' => 'nullable|string|in:ltr,rtl',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $language = Language::create($validated);

        if ($language->is_default) {
            Language::where('id', '!=', $language->id)->update(['is_default' => false]);
        }

        return $this->createdResponse(new LanguageResource($language), 'Language created.');
    }

    public function update(Language $language, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'sometimes|string|max:80',
            'code'      => 'sometimes|string|max:10|unique:languages,code,' . $language->id,
            'direction' => 'nullable|string|in:ltr,rtl',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $language->update($validated);

        if (!empty($validated['is_default'])) {
            Language::where('id', '!=', $language->id)->update(['is_default' => false]);
        }

        return $this->successResponse(new LanguageResource($language->fresh()), 'Language updated.');
    }

    public function destroy(Language $language): JsonResponse
    {
        if ($language->is_default) {
            return $this->errorResponse('Cannot delete the default language.', null, 400);
        }
        $language->delete();
        return $this->noContentResponse('Language deleted.');
    }
}
