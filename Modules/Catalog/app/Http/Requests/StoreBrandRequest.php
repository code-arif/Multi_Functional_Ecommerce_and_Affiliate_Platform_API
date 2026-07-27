<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string|max:1000',
            'logo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'website'          => 'nullable|url|max:255',
            'meta_title'       => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Brand name is required.',
        ];
    }
}
