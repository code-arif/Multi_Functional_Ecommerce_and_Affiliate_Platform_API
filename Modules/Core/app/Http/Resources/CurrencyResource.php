<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CurrencyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'code'          => $this->code,
            'symbol'        => $this->symbol,
            'exchange_rate' => (float) $this->exchange_rate,
            'precision'     => $this->precision,
            'is_default'    => $this->is_default,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at,
        ];
    }
}
