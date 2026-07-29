<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorPayoutResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'amount'          => (float) $this->amount,
            'balance_before'  => (float) $this->balance_before,
            'balance_after'   => (float) $this->balance_after,
            'payment_method'  => $this->payment_method,
            'notes'           => $this->notes,
            'status'          => $this->status,
            'admin_notes'     => $this->admin_notes,
            'approved_at'     => $this->approved_at,
            'completed_at'    => $this->completed_at,
            'created_at'      => $this->created_at,
        ];
    }
}
