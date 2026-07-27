<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorStaffResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'user'        => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
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
