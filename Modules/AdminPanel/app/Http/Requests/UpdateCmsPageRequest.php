<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id ?? $this->route('page');
        return [
            'title'           => 'sometimes|string|max:200',
            'slug'            => 'nullable|string|max:200|unique:cms_pages,slug,' . $pageId,
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
        ];
    }
}
