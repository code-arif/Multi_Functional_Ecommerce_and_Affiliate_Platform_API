<?php

namespace Modules\Affiliate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateDashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'total_clicks'         => $this['total_clicks'],
            'total_conversions'    => $this['total_conversions'],
            'approved_conversions' => $this['approved_conversions'],
            'conversion_rate'      => $this['conversion_rate'],
            'available_balance'    => $this['available_balance'],
            'lifetime_earnings'    => $this['lifetime_earnings'],
            'pending_earnings'     => $this['pending_earnings'],
            'total_paid'           => $this['total_paid'],
        ];
    }
}
