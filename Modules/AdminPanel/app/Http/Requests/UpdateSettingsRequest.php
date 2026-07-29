<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        return [
            'settings'          => 'required|array',
            'settings.*.key'    => 'required|string|max:100',
            'settings.*.value'  => 'nullable|string',
        ];
    }
}
