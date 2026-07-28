<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_zone_id' => 'sometimes|exists:shipping_zones,id',
            'courier_id'       => 'sometimes|exists:couriers,id',
            'name'             => 'sometimes|string|max:100',
            'method'           => 'sometimes|string|max:50|in:standard,express,same_day,overnight,freight',
            'base_rate'        => 'sometimes|numeric|min:0',
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
}
