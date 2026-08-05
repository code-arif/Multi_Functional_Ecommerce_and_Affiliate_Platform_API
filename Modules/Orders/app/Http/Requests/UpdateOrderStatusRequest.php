<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Orders\Enums\OrderStatus;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via middleware + policy
    }

    public function rules(): array
    {
        return [
            'status'          => ['required', 'string', Rule::in(OrderStatus::values())],
            'note'            => 'nullable|string|max:1000',
            'notify_customer' => 'boolean',
            'tracking_number' => 'nullable|string|max:100',
            'shipping_carrier'=> 'nullable|string|max:100',
        ];
    }
}
