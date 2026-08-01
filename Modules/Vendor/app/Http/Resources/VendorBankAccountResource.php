<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorBankAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'bank_name'               => $this->bank_name,
            'branch_name'             => $this->branch_name,
            'account_name'            => $this->account_name,
            'account_number'          => $this->account_number,
            'routing_number'          => $this->routing_number,
            'swift_code'              => $this->swift_code,
            'mobile_banking_provider' => $this->mobile_banking_provider,
            'mobile_banking_number'   => $this->mobile_banking_number,
            'is_default'              => $this->is_default,
        ];
    }
}
