<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendorTrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number'  => 'required|string|max:100',
            'shipping_carrier' => 'nullable|string|max:100',
        ];
    }
}
