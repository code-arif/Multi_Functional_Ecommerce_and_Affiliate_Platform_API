<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadSettingFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,svg,pdf|max:5120',
        ];
    }
}
