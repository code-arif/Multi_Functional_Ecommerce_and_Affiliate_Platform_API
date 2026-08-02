<?php

namespace Modules\Support\Http\Controllers;

use Modules\Support\Models\Faq;
use Modules\Support\Models\FaqCategory;
use Modules\Support\Http\Resources\FaqResource;
use Modules\Support\Http\Resources\FaqCategoryResource;
use Modules\Support\Http\Requests\StoreFaqRequest;
use Modules\Support\Http\Requests\UpdateFaqRequest;
use Modules\Support\Http\Requests\StoreFaqCategoryRequest;
use Modules\Support\Http\Requests\UpdateFaqCategoryRequest;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController
{
    use ApiResponse;

    // ─── Public Endpoints ─────────────────────────────────────

    public function publicCategories(): JsonResponse
    {
        $categories = FaqCategory::with(['faqs' => fn($q) => $q->active()->ordered()])
            ->active()
            ->ordered()
            ->get();

        return $this->successResponse(FaqCategoryResource::collection($categories));
    }

    public function publicFaqs(): JsonResponse
    {
        $faqs = Faq::with('category')
            ->active()
            ->ordered()
            ->paginate(50);

        return $this->paginatedResponse(FaqResource::collection($faqs));
    }

    // ─── Admin Category Management ────────────────────────────

    public function categories(): JsonResponse
    {
        $categories = FaqCategory::withCount('faqs')
            ->ordered()
            ->paginate(20);

        return $this->paginatedResponse(FaqCategoryResource::collection($categories));
    }

    public function storeCategory(StoreFaqCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (!isset($data['slug'])) {
            $data['slug'] = \Str::slug($data['name']);
        }

        $category = FaqCategory::create($data);
        return $this->createdResponse(new FaqCategoryResource($category), 'Category created.');
    }

    public function updateCategory(FaqCategory $faqCategory, UpdateFaqCategoryRequest $request): JsonResponse
    {
        $faqCategory->update($request->validated());
        return $this->successResponse(new FaqCategoryResource($faqCategory->fresh()), 'Category updated.');
    }

    public function destroyCategory(FaqCategory $faqCategory): JsonResponse
    {
        $faqCategory->faqs()->update(['category_id' => null]);
        $faqCategory->delete();
        return $this->noContentResponse('Category deleted.');
    }

    // ─── Admin FAQ Management ─────────────────────────────────

    public function index(): JsonResponse
    {
        $faqs = Faq::with('category')
            ->ordered()
            ->paginate(20);

        return $this->paginatedResponse(FaqResource::collection($faqs));
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (!empty($data['category_uuid'])) {
            $data['category_id'] = FaqCategory::findByUuidOrFail($data['category_uuid'])->id;
        }
        unset($data['category_uuid']);

        $faq = Faq::create($data);
        return $this->createdResponse(new FaqResource($faq->load('category')), 'FAQ created.');
    }

    public function show(Faq $faq): JsonResponse
    {
        return $this->successResponse(new FaqResource($faq->load('category')));
    }

    public function update(Faq $faq, UpdateFaqRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('category_uuid', $data)) {
            $data['category_id'] = !empty($data['category_uuid'])
                ? FaqCategory::findByUuidOrFail($data['category_uuid'])->id
                : null;
        }
        unset($data['category_uuid']);

        $faq->update($data);
        return $this->successResponse(new FaqResource($faq->fresh()->load('category')), 'FAQ updated.');
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();
        return $this->noContentResponse('FAQ deleted.');
    }
}
