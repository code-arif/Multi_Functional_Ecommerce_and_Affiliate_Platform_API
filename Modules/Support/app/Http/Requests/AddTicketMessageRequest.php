<?php

namespace Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via middleware + policy
    }

    public function rules(): array
    {
        return [
            'message'     => 'required|string|max:5000',
            'attachments' => 'nullable|array',
            'attachments.*' => 'string|max:500',
        ];
    }
}
