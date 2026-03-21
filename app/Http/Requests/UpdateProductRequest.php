<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
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
            'manage_stock'      => 'boolean',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'thumbnail'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_featured'       => 'boolean',
            'is_new'            => 'boolean',
            'is_bestseller'     => 'boolean',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'status'            => 'sometimes|in:active,inactive,draft',
            'tags'              => 'nullable|array',
            'images'            => 'nullable|array',
            'attributes'        => 'nullable|array',
            'variants'          => 'nullable|array',
        ];
    }
}
