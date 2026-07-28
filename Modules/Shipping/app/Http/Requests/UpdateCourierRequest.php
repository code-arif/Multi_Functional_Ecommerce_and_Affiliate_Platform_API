<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => 'sometimes|string|max:100',
            'slug'               => ['nullable', 'string', 'max:120', Rule::unique('couriers', 'slug')->ignore($this->route('courier'))],
            'display_name'       => 'nullable|string|max:150',
            'description'        => 'nullable|string|max:1000',
            'website'            => 'nullable|url|max:255',
            'tracking_url_template' => 'nullable|string|max:500',
            'contact_phone'      => 'nullable|string|max:30',
            'contact_email'      => 'nullable|email|max:100',
            'supported_services' => 'nullable|array',
            'supported_services.*' => 'string|max:50',
            'is_active'          => 'boolean',
            'sort_order'         => 'nullable|integer|min:0',
        ];
    }
}
