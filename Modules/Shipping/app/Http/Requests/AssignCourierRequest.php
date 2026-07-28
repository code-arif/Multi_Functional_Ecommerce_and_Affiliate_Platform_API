<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'courier_id'        => 'required|exists:couriers,id',
            'tracking_number'   => 'nullable|string|max:100',
            'carrier_tracking_code' => 'nullable|string|max:100',
            'shipping_cost'     => 'nullable|numeric|min:0',
        ];
    }
}
