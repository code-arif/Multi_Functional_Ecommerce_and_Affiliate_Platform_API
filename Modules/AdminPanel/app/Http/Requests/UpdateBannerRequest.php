<?php

namespace Modules\AdminPanel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorized via policy + middleware
    }

    public function rules(): array
    {
        return [
            'title'        => 'sometimes|string|max:200',
            'subtitle'     => 'nullable|string|max:500',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'mobile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'link'         => 'nullable|url|max:500',
            'position'     => 'sometimes|string|max:50',
            'sort_order'   => 'nullable|integer|min:0',
            'is_active'    => 'boolean',
            'starts_at'    => 'nullable|date',
            'expires_at'   => 'nullable|date|after:starts_at',
        ];
    }
}
