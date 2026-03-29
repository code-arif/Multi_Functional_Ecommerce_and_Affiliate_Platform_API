<?php

namespace App\Http\Requests\CMS;

use Illuminate\Foundation\Http\FormRequest;

class StoreCmsPageRequest extends FormRequest
{
    /**
     * Authorize the request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'template' => 'nullable|string|max:50',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Optional: Custom error messages
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Page title is required.',
            'title.max' => 'Title cannot exceed 255 characters.',
        ];
    }

    public function validatedWithExtras(): array
    {
        return array_merge($this->validated(), [
            'created_by' => auth()->id()
        ]);
    }
}
