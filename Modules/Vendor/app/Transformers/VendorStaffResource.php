<?php

namespace Modules\Vendor\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorStaffResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'user'        => $this->whenLoaded('user', fn() => [
                'uuid'  => $this->user?->uuid,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ]),
            'role'        => $this->role,
            'permissions' => $this->permissions,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at,
        ];
    }
}
