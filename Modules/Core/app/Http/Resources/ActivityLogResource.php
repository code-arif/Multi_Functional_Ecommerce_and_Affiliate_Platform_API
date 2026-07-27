<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'user'           => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'module'         => $this->module,
            'action'         => $this->action,
            'subject_type'   => $this->subject_type,
            'subject_id'     => $this->subject_id,
            'old_values'     => $this->old_values,
            'new_values'     => $this->new_values,
            'ip'             => $this->ip,
            'created_at'     => $this->created_at,
        ];
    }
}
