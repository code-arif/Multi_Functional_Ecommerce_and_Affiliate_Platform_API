<?php

namespace Modules\Vendor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'          => 'required|numeric|min:100|max:9999999',
            'payment_method'  => 'nullable|string|max:50',
            'payment_details' => 'nullable|string|max:500',
            'notes'           => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Payout amount is required.',
            'amount.min'      => 'Minimum payout amount is 100.',
            'amount.numeric'  => 'Amount must be a number.',
        ];
    }
}
