<?php

namespace Modules\Shipping\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'courier_id'      => 'required|exists:couriers,id',
            'pickup_date'     => 'required|date|after_or_equal:today',
            'pickup_time_from' => 'required|date_format:H:i',
            'pickup_time_to'  => 'required|date_format:H:i|after:pickup_time_from',
            'address'         => 'required|string|max:500',
            'contact_name'    => 'required|string|max:100',
            'contact_phone'   => 'required|string|max:30',
            'notes'           => 'nullable|string|max:1000',
            'parcels'         => 'nullable|array',
            'parcels.*.weight' => 'nullable|numeric|min:0',
            'parcels.*.quantity' => 'nullable|integer|min:1',
            'parcels.*.description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'pickup_time_to.after' => 'Pickup end time must be after start time.',
            'pickup_date.after_or_equal' => 'Pickup date must be today or later.',
        ];
    }
}
