<?php

namespace Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_uuid' => 'nullable|exists:faq_categories,uuid',
            'question'    => 'required|string|max:500',
            'answer'      => 'required|string',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ];
    }
}
