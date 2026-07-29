<?php

namespace Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'   => 'required|exists:orders,id',
            'subject'    => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ];
    }
}
