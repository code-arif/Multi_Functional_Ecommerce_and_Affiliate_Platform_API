<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewCancelRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'         => ['required', 'string', Rule::in(['approved', 'rejected'])],
            'admin_response' => 'nullable|string|max:1000',
        ];
    }
}
