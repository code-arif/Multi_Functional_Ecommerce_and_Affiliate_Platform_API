<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar_url' => $this->avatar_url,
            'status'     => $this->status,
            'roles'      => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
