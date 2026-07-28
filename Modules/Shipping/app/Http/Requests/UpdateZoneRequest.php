<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|string|max:100',
            'slug'        => ['nullable', 'string', 'max:120', Rule::unique('shipping_zones', 'slug')->ignore($this->route('zone'))],
            'description' => 'nullable|string|max:1000',
            'countries'   => 'nullable|array',
            'countries.*' => 'string|max:5',
            'states'      => 'nullable|array',
            'states.*'    => 'string|max:100',
            'cities'      => 'nullable|array',
            'cities.*'    => 'string|max:100',
            'postal_codes' => 'nullable|array',
            'postal_codes.*' => 'string|max:20',
            'is_active'   => 'boolean',
        ];
    }
}
