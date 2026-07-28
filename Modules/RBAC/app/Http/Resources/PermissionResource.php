<?php

namespace Modules\RBAC\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'group'        => $this->group,
            'guard_name'   => $this->guard_name,
            'roles_count'  => $this->whenCounted('roles', $this->roles_count),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
