<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_zone_uuid' => 'required|exists:shipping_zones,uuid',
            'courier_uuid' => 'required|exists:couriers,uuid',
            'name'             => 'required|string|max:100',
            'method'           => 'required|string|max:50|in:standard,express,same_day,overnight,freight',
            'base_rate'        => 'required|numeric|min:0',
            'rate_per_kg'      => 'nullable|numeric|min:0',
            'rate_per_item'    => 'nullable|numeric|min:0',
            'free_shipping_min' => 'nullable|numeric|min:0',
            'max_weight'       => 'nullable|numeric|min:0',
            'estimated_days_min' => 'nullable|integer|min:1',
            'estimated_days_max' => 'nullable|integer|min:1',
            'conditions'       => 'nullable|array',
            'is_active'        => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_zone_id.required' => 'Shipping zone is required.',
            'courier_id.required'       => 'Courier is required.',
        ];
    }
}
