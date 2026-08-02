<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'         => 'required|string|max:50|in:pending,processing,picked_up,in_transit,out_for_delivery,delivered,failed,returned,cancelled',
            'tracking_number' => 'nullable|string|max:100',
            'location'       => 'nullable|string|max:255',
            'description'    => 'nullable|string|max:1000',
            'notes'          => 'nullable|string|max:2000',
        ];
    }
}
