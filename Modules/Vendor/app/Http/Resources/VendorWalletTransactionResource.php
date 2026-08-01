<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorWalletTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'type'           => $this->type,
            'amount'         => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after'  => (float) $this->balance_after,
            'description'    => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id'   => $this->reference_id,
            'status'         => $this->status,
            'created_at'     => $this->created_at,
        ];
    }
}
