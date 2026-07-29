<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        return [
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'note'   => 'nullable|string|max:500',
        ];
    }
}
