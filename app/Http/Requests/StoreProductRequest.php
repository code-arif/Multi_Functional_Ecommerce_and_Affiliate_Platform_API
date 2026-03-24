<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:255',
            'category_id'         => 'nullable|exists:categories,id',
            'brand_id'            => 'nullable|exists:brands,id',
            'type'                => 'required|in:simple,variable,affiliate',
            'sku'                 => 'nullable|string|unique:products,sku',
            'price'               => 'required_if:type,simple|numeric|min:0',
            'sale_price'          => 'nullable|numeric|min:0|lt:price',
            'cost_price'          => 'nullable|numeric|min:0',
            'stock_quantity'      => 'required_if:type,simple|integer|min:0',
            'manage_stock'        => 'sometimes|boolean',
            'short_description'   => 'nullable|string|max:500',
            'description'         => 'nullable|string',
            'thumbnail'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'weight'              => 'nullable|numeric|min:0',
            'tags'                => 'nullable|array',
            'is_featured'         => 'sometimes|boolean',
            'is_new'              => 'sometimes|boolean',
            'is_bestseller'       => 'sometimes|boolean',
            'meta_title'          => 'nullable|string|max:255',
            'meta_description'    => 'nullable|string|max:500',
            'meta_keywords'       => 'nullable|string|max:255',
            'status'              => 'required|in:active,inactive,draft',
            // Images
            'images'              => 'nullable|array',
            'images.*.path'       => 'required_with:images|string',
            'images.*.is_primary' => 'boolean',
            'images.*.alt_text'   => 'nullable|string|max:255',
            // Attributes (for variable)
            'attributes'              => 'required_if:type,variable|array',
            'attributes.*.name'       => 'required_with:attributes|string',
            'attributes.*.values'     => 'required_with:attributes|array|min:1',
            'attributes.*.values.*.value' => 'required|string',
            // Variants (for variable)
            'variants'                    => 'required_if:type,variable|array',
            'variants.*.attributes'       => 'required_with:variants|array',
            'variants.*.price'            => 'required_with:variants|numeric|min:0',
            'variants.*.stock_quantity'   => 'integer|min:0',
        ];
    }


    protected function prepareForValidation(): void
    {
        $casts = [];

        // Boolean fields: "true"/"1"/1 → true
        foreach (['manage_stock', 'is_featured', 'is_new', 'is_bestseller'] as $field) {
            if ($this->has($field)) {
                $casts[$field] = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN);
            }
        }

        // JSON string arrays → real arrays
        foreach (['tags', 'attributes', 'variants', 'images'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $decoded = json_decode($this->input($field), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $casts[$field] = $decoded;
                }
            }
        }

        $this->merge($casts);
    }
}
