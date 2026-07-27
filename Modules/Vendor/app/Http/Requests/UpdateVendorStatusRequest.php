<?php

namespace Modules\Vendor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required_if:action,reject,suspend|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'A reason is required when rejecting or suspending a vendor.',
        ];
    }
}
