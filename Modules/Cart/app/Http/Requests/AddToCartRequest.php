<?php

namespace Modules\Cart\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_uuid' => 'required|exists:products,uuid',
            'variant_uuid' => 'nullable|exists:product_variants,uuid',
            'quantity'   => 'integer|min:1|max:100',
        ];
    }
}
