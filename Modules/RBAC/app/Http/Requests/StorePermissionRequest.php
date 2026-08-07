<?php

namespace Modules\RBAC\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:permissions,name',
            'display_name' => 'nullable|string|max:150',
            'group' => 'nullable|string|max:50',
            'guard_name' => 'nullable|string|max:50|in:web,api',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A permission with this name already exists.',
            'name.required' => 'Permission name is required.',
        ];
    }
}
