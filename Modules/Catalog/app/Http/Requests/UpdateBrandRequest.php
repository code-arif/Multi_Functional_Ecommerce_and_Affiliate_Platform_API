<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'sometimes|string|max:100',
            'description'      => 'nullable|string|max:1000',
            'logo'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'website'          => 'nullable|url|max:255',
            'meta_title'       => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
            'sort_order'       => 'nullable|integer|min:0',
            'is_active'        => 'boolean',
        ];
    }
}
