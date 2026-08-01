<?php

namespace Modules\Vendor\Services;

use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Events\VendorRegisteredEvent;
use Modules\Vendor\Events\VendorRejectedEvent;
use Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VendorService
{
    public function register(array $data, User $user): Vendor
    {
        $vendor = DB::transaction(function () use ($data, $user) {
            $vendor = Vendor::create([
                'user_id'  => $user->id,
                'shop_name' => $data['shop_name'],
                'slug' => $this->generateUniqueSlug($data['shop_name']),
                'email' => $data['email'] ?? $user->email,
                'phone' => $data['phone'] ?? $user->phone,
                'description' => $data['description'] ?? null,
                'status' => 'pending',
                'commission_rate' => config('ecommerce.vendor.default_commission_rate', 10),
                'commission_type' => 'percentage',
            ]);

            // Create profile
            $vendor->profile()->create([
                'business_type' => $data['business_type'] ?? null,
                'business_registration_number' => $data['registration_number'] ?? null,
                'website' => $data['website'] ?? null,
            ]);

            // Create address if provided
            if (!empty($data['address'])) {
                $vendor->addresses()->create(array_merge($data['address'], [
                    'is_default' => true,
                ]));
            }

            // Assign vendor role
            $user->assignRole('vendor');

            return $vendor;
        });

        // Dispatch event outside transaction
        VendorRegisteredEvent::dispatch($vendor);

        return $vendor;
    }

    public function addToWallet(Vendor $vendor, int|float $amount, string $description = '', ?string $referenceType = null, ?int $referenceId = null): Vendor
    {
        return DB::transaction(function () use ($vendor, $amount, $description, $referenceType, $referenceId) {
            $before = $vendor->wallet_balance;
            $after  = $before + $amount;

            $vendor->walletTransactions()->create([
                'type'           => 'commission',
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'description'    => $description,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'status'         => 'completed',
            ]);

            $vendor->increment('wallet_balance', $amount);
            $vendor->increment('total_earned', $amount);

            return $vendor->fresh();
        });
    }

    public function withdrawFromWallet(Vendor $vendor, int|float $amount, string $description = '', ?string $referenceType = null, ?int $referenceId = null): Vendor
    {
        if ($vendor->wallet_balance < $amount) {
            abort(400, 'Insufficient wallet balance.');
        }

        return DB::transaction(function () use ($vendor, $amount, $description, $referenceType, $referenceId) {
            $before = $vendor->wallet_balance;
            $after  = $before - $amount;

            $vendor->walletTransactions()->create([
                'type'           => 'withdrawal',
                'amount'         => -$amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'description'    => $description,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'status'         => 'completed',
            ]);

            $vendor->decrement('wallet_balance', $amount);
            $vendor->increment('total_withdrawn', $amount);

            return $vendor->fresh();
        });
    }

    public function addStaff(Vendor $vendor, User $user, string $role = 'staff', array $permissions = []): \Modules\Vendor\Models\VendorStaff
    {
        return $vendor->staff()->create([
            'user_id'     => $user->id,
            'role'        => $role,
            'permissions' => $permissions,
            'is_active'   => true,
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (Vendor::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
