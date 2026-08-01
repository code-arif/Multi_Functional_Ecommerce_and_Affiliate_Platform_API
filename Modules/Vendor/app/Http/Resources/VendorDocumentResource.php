<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorDocumentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'type'             => $this->type,
            'document_url'     => $this->document_url,
            'document_number'  => $this->document_number,
            'expiry_date'      => $this->expiry_date,
            'status'           => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'verified_at'      => $this->verified_at,
        ];
    }
}
