<?php

namespace Modules\Payments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'transaction_id' => $this->transaction_id,
            'type'           => $this->type,
            'amount'         => (float) $this->amount,
            'fee'            => (float) $this->fee,
            'net'            => (float) $this->net,
            'currency'       => $this->currency,
            'status'         => $this->status,
            'notes'          => $this->notes,
            'created_at'     => $this->created_at,
        ];
    }
}
