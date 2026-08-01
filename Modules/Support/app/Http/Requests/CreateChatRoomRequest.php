<?php

namespace Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateChatRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_uuid' => 'nullable|exists:orders,uuid',
            'subject'  => 'required|string|max:255',
        ];
    }
}
