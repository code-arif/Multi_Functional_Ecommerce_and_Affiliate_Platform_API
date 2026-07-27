<?php

namespace Modules\Vendor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'            => 'required|string|in:trade_license,nid,bin,tin,passport',
            'document'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'document_number' => 'nullable|string|max:100',
            'expiry_date'     => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'     => 'Document type is required.',
            'type.in'           => 'Invalid document type. Valid types: trade_license, nid, bin, tin, passport.',
            'document.required' => 'Document file is required.',
            'document.max'      => 'Document file must not exceed 10MB.',
        ];
    }
}
