<?php

namespace Modules\Payments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'order_uuid'        => optional($this->order)?->uuid,
            'transaction_id'  => $this->transaction_id,
            'payment_method'  => $this->payment_method ?? $this->gateway,
            'payment_status'  => $this->payment_status ?? $this->status,
            'amount'          => (float) $this->amount,
            'currency'        => $this->currency,
            'gateway'         => $this->gateway,
            'paid_at'         => $this->paid_at,
            'created_at'      => $this->created_at,
            'transactions'    => TransactionResource::collection($this->whenLoaded('transactions')),
            'refunds'         => RefundResource::collection($this->whenLoaded('refunds')),
        ];
    }
}
