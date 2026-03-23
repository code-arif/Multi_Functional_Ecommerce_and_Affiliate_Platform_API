<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'label'          => 'nullable|string|max:50|in:home,office,other',
            'recipient_name' => 'required|string|min:2|max:100',
            'phone'          => ['required', 'string', 'max:20', 'regex:/^[\+]?[\d\s\-\(\)]{7,20}$/'],
            'email'          => 'nullable|email|max:150',
            'address_line1'  => 'required|string|min:5|max:500',
            'address_line2'  => 'nullable|string|max:500',
            'city'           => 'required|string|min:2|max:100',
            'state'          => 'nullable|string|max:100',
            'postal_code'    => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',
            'is_default'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
