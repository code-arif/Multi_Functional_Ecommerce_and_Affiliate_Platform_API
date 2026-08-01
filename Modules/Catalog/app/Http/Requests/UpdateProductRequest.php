<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            'category_uuid' => 'sometimes|exists:categories,uuid',
            'brand_uuid' => 'nullable|exists:brands,uuid',
            'name'        => 'sometimes|string|max:200',
            'sku'         => 'sometimes|string|max:100|unique:products,sku,' . $productId,
            'type'        => 'sometimes|string|in:simple,variable',
            'price'       => 'sometimes|numeric|min:0',
            'sale_price'  => 'nullable|numeric|min:0',
            'cost_price'  => 'nullable|numeric|min:0',
            'stock_quantity'    => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'manage_stock'      => 'boolean',
            'stock_status'      => 'nullable|string|in:in_stock,out_of_stock,on_backorder',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'thumbnail'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'weight'            => 'nullable|numeric|min:0',
            'weight_unit'       => 'nullable|string|max:10',
            'tags'              => 'nullable|array',
            'is_featured'       => 'boolean',
            'is_new'            => 'boolean',
            'meta_title'        => 'nullable|string|max:100',
            'meta_description'  => 'nullable|string|max:255',
            'meta_keywords'     => 'nullable|string|max:255',
            'status'            => 'sometimes|string|in:active,inactive,draft',
            'published_at'      => 'nullable|date',
        ];
    }
}
