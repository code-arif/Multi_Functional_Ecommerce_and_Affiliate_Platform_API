<?php

namespace Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via middleware + policy
    }

    public function rules(): array
    {
        return [
            'order_uuid' => 'nullable|exists:orders,uuid',
            'category'   => 'required|string|in:general,order,payment,shipping,refund,technical,other',
            'subject'    => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'priority'   => 'nullable|string|in:low,medium,high,urgent',
        ];
    }

    public function messages(): array
    {
        return [
            'category.in' => 'Invalid category. Choose: general, order, payment, shipping, refund, technical, or other.',
        ];
    }
}
