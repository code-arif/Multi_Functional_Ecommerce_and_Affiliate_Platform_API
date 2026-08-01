<?php

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/policy
    }

    public function rules(): array
    {
        return [
            'vendor_product_uuid' => 'required|exists:vendor_product_prices,uuid',
            'quantity'          => 'required|integer|not_in:0',
            'notes'             => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'vendor_product_id.required' => 'The product is required.',
            'vendor_product_id.exists'   => 'The selected product does not exist.',
            'quantity.required'          => 'The adjustment quantity is required.',
            'quantity.integer'           => 'The quantity must be a whole number.',
            'quantity.not_in'            => 'The quantity cannot be zero.',
        ];
    }
}
