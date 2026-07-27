<?php

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'vendor_id'       => $this->vendor_id,
            'amount'          => (float) $this->amount,
            'balance_before'  => (float) $this->balance_before,
            'balance_after'   => (float) $this->balance_after,
            'payment_method'  => $this->payment_method,
            'notes'           => $this->notes,
            'status'          => $this->status,
            'is_pending'      => $this->is_pending,
            'is_completed'    => $this->is_completed,
            'admin_notes'     => $this->admin_notes,
            'approved_at'     => $this->approved_at,
            'completed_at'    => $this->completed_at,
            'created_at'      => $this->created_at,
        ];
    }
}
