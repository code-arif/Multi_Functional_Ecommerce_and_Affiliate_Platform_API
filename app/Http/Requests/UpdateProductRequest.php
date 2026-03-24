<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        return [
            'name'              => 'sometimes|string|max:255',
            'category_id'       => 'nullable|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'sku'               => "nullable|string|unique:products,sku,{$productId}",
            'price'             => 'sometimes|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
            'cost_price'        => 'nullable|numeric|min:0',
            'stock_quantity'    => 'sometimes|integer|min:0',
            'manage_stock'      => 'sometimes|boolean',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'thumbnail'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_featured'       => 'sometimes|boolean',
            'is_new'            => 'sometimes|boolean',
            'is_bestseller'     => 'sometimes|boolean',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'status'            => 'sometimes|in:active,inactive,draft',
            'tags'              => 'nullable|array',
            'images'            => 'nullable|array',
            'attributes'        => 'nullable|array',
            'variants'          => 'nullable|array',
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
