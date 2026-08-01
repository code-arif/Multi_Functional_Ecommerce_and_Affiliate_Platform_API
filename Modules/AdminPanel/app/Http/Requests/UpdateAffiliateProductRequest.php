<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAffiliateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        return [
            'title'            => 'sometimes|string|max:200',
            'slug'             => 'sometimes|string|max:200|unique:affiliate_products,slug,' . $this->route('affiliate_product'),
            'category_uuid' => 'nullable|exists:categories,uuid',
            'description'      => 'nullable|string',
            'display_price'    => 'nullable|numeric|min:0',
            'thumbnail'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'affiliate_link'   => 'sometimes|url|max:1000',
            'source_platform'  => 'sometimes|string|max:100',
            'commission_type'  => 'sometimes|string|in:fixed,percentage',
            'commission_value' => 'sometimes|numeric|min:0',
            'is_featured'      => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'nullable|integer|min:0',
            'meta_title'       => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:255',
        ];
    }
}
