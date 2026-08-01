<?php

namespace Modules\Payments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'refund_transaction_id' => $this->refund_transaction_id,
            'amount'            => (float) $this->amount,
            'fee'               => (float) $this->fee,
            'currency'          => $this->currency,
            'reason'            => $this->reason,
            'status'            => $this->status,
            'processed_at'      => $this->processed_at,
            'created_at'        => $this->created_at,
        ];
    }
}
