<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'shipping_name'          => 'required|string|max:100',
            'shipping_phone'         => 'required|string|max:20',
            'shipping_email'         => 'nullable|email',
            'shipping_address_line1' => 'required|string|max:500',
            'shipping_address_line2' => 'nullable|string|max:500',
            'shipping_city'          => 'required|string|max:100',
            'shipping_state'         => 'nullable|string|max:100',
            'shipping_postal_code'   => 'nullable|string|max:20',
            'shipping_country'       => 'nullable|string|max:100',
            'payment_method'         => 'required|in:cod,bkash,nagad,sslcommerz,card',
            'customer_note'          => 'nullable|string|max:1000',
            'save_address'           => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_name.required'          => 'Recipient name is required.',
            'shipping_phone.required'         => 'Phone number is required.',
            'shipping_address_line1.required' => 'Address is required.',
            'shipping_city.required'          => 'City is required.',
            'payment_method.in'               => 'Invalid payment method selected.',
        ];
    }
}
