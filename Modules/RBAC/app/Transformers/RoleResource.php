<?php

namespace Modules\RBAC\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'display_name'  => $this->display_name,
            'description'   => $this->description,
            'guard_name'    => $this->guard_name,
            'users_count'   => $this->whenCounted('users', $this->users_count),
            'permissions'   => PermissionResource::collection($this->whenLoaded('permissions')),
            'permissions_list' => $this->whenLoaded('permissions', fn() => $this->permissions->pluck('name')),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}

