<?php

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'vendor_id'        => $this->vendor_id,
            'period_label'     => $this->period_label,
            'period_start'     => $this->period_start,
            'period_end'       => $this->period_end,
            'total_sales'      => (float) $this->total_sales,
            'total_commission' => (float) $this->total_commission,
            'net_earnings'     => (float) $this->net_earnings,
            'total_paid'       => (float) $this->total_paid,
            'balance_carried'  => (float) $this->balance_carried,
            'status'           => $this->status,
            'is_draft'         => $this->is_draft,
            'is_finalized'     => $this->is_finalized,
            'finalized_at'     => $this->finalized_at,
            'created_at'       => $this->created_at,
        ];
    }
}
