<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'shop_name'        => $this->shop_name,
            'slug'             => $this->slug,
            'email'            => $this->email,
            'phone'            => $this->phone,
            'description'      => $this->description,
            'logo_url'         => $this->logo_url,
            'banner_url'       => $this->banner_url,
            'status'           => $this->status,
            'commission_rate'  => (float) $this->commission_rate,
            'commission_type'  => $this->commission_type,
            'wallet_balance'   => (float) $this->wallet_balance,
            'total_earned'     => (float) $this->total_earned,
            'total_withdrawn'  => (float) $this->total_withdrawn,
            'is_active'        => $this->is_active,
            'is_pending'       => $this->is_pending,
            'approved_at'      => $this->approved_at,
            'rejection_reason' => $this->rejection_reason,
            'profile'          => $this->whenLoaded('profile', fn() => VendorProfileResource::make($this->profile)),
            'addresses'        => VendorAddressResource::collection($this->whenLoaded('addresses')),
            'bank_accounts'    => VendorBankAccountResource::collection($this->whenLoaded('bankAccounts')),
            'documents'        => VendorDocumentResource::collection($this->whenLoaded('documents')),
            'staff'            => VendorStaffResource::collection($this->whenLoaded('staff')),
            'user'             => $this->whenLoaded('user', fn() => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
