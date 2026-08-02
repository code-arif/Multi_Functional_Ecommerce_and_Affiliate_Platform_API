<?php

namespace Modules\Vendor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_name' => ['required', 'string', 'max:200', Rule::unique('vendors', 'shop_name')],
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
            'business_type' => 'nullable|string|max:100',
            'registration_number' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|array',
            'address.address_line_1' => 'required_with:address|string|max:255',
            'address.address_line_2' => 'nullable|string|max:255',
            'address.city' => 'required_with:address|string|max:100',
            'address.state' => 'nullable|string|max:100',
            'address.postal_code' => 'nullable|string|max:20',
            'address.country' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'shop_name.required' => 'Shop name is required.',
            'shop_name.unique' => 'This shop name is already taken.',
            'address.address_line_1.required_with' => 'Address line 1 is required when address is provided.',
            'address.city.required_with' => 'City is required when address is provided.',
        ];
    }
}
