<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:active,inactive,banned',
        ];
    }
}
