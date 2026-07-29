<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        return [
            'code'                 => 'required|string|max:50|unique:coupons,code',
            'type'                 => 'required|string|in:fixed,percentage',
            'value'                => 'required|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'maximum_discount'     => 'nullable|numeric|min:0',
            'usage_limit'          => 'nullable|integer|min:0',
            'usage_per_user'       => 'nullable|integer|min:0',
            'is_active'            => 'boolean',
            'starts_at'            => 'nullable|date',
            'expires_at'           => 'nullable|date|after:starts_at',
            'description'          => 'nullable|string|max:500',
        ];
    }
}
