<?php

namespace Modules\Checkout\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_uuid' => 'nullable|exists:addresses,uuid',
            'shipping_address' => 'required_without:address_uuid',
            'billing_address'  => 'sometimes',
            'shipping_method'  => 'nullable|string|max:50|in:standard,express',
            'payment_method'   => 'nullable|string|max:50|in:cod,stripe',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'tax_amount'       => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'address_uuid.exists'            => 'The selected address does not exist.',
            'shipping_address.required_without' => 'Please provide a shipping address or select a saved address.',
            'payment_method.in'            => 'Invalid payment method. Accepted: cod, stripe.',
            'shipping_method.in'           => 'Invalid shipping method. Accepted: standard, express.',
        ];
    }
}
