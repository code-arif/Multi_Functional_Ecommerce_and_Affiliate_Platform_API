<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password'      => 'required|string',
            'password'              => [
                'required',
                'string',
                'min:8',
                'max:128',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', // at least 1 upper, 1 lower, 1 digit
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!Hash::check($this->current_password, $this->user()->password)) {
                $validator->errors()->add('current_password', 'Current password is incorrect.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ];
    }
}
