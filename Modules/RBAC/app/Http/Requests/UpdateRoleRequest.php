<?php

namespace Modules\RBAC\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['sometimes', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($this->route('role'))],
            'display_name' => 'nullable|string|max:150',
            'description'  => 'nullable|string|max:500',
            'guard_name'   => 'nullable|string|max:50|in:web,api',
            'permissions'  => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'         => 'A role with this name already exists.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }
}
