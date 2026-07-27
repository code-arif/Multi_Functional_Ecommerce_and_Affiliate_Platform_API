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
            'shipping_address' => 'required',
            'billing_address'  => 'sometimes',
            'shipping_method'  => 'nullable|string|max:50',
            'payment_method'   => 'nullable|string|max:50',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'tax_amount'       => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string|max:1000',
        ];
    }
}
