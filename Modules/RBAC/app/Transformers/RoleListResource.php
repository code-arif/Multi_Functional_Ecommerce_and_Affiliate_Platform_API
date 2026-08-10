<?php

namespace Modules\RBAC\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'guard_name' => $this->guard_name,
            'users_count' => $this->whenCounted('users', $this->users_count),
            'permissions_count' => $this->whenCounted('permissions', $this->permissions_count),
            'created_at' => $this->created_at,
        ];
    }
}

