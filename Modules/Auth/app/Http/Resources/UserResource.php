<?php

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'avatar'            => $this->avatar_url,
            'status'            => $this->status,
            'roles'             => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'default_address'   => $this->whenLoaded('defaultAddress'),
            'email_verified_at' => $this->email_verified_at,
            'is_active'         => $this->is_active,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
