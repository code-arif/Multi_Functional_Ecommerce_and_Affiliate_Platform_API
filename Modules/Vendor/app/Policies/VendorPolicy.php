<?php

namespace Modules\Vendor\Policies;

use Modules\Auth\Models\User;
use Modules\Vendor\Models\Vendor;

class VendorPolicy
{
    /**
     * Determine whether any user can view public vendor listings.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether a user can view a specific vendor.
     * - Owner can always view their own vendor
     * - Anyone can view active vendors
     * - Admins can view any vendor
     */
    public function view(?User $user, Vendor $vendor): bool
    {
        // Owner can always view their own vendor
        if ($user && $user->id === $vendor->user_id) {
            return true;
        }

        // Anyone can view active vendors
        if ($vendor->status === 'active') {
            return true;
        }

        // Admin with permission can view any vendor
        if ($user && $user->hasPermission('vendors.view')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether a user can create a vendor application.
     */
    public function create(User $user): bool
    {
        // User must not already have a vendor application
        return !$user->vendor()->exists();
    }

    /**
     * Determine whether the user can update their vendor profile.
     */
    public function update(User $user, Vendor $vendor): bool
    {
        return $user->id === $vendor->user_id;
    }

    /**
     * Determine whether the user can delete a vendor.
     */
    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->id === $vendor->user_id || $user->hasRole('admin');
    }

    /**
     * Admin: view any vendor (including pending/rejected).
     */
    public function viewAnyAdmin(User $user): bool
    {
        return $user->hasPermission('vendors.view');
    }

    /**
     * Admin: approve or reject a vendor.
     */
    public function approve(User $user): bool
    {
        return $user->hasPermission('vendors.approve');
    }

    /**
     * Admin: suspend a vendor.
     */
    public function suspend(User $user): bool
    {
        return $user->hasPermission('vendors.manage');
    }

    /**
     * Admin: verify vendor documents.
     */
    public function verifyDocuments(User $user): bool
    {
        return $user->hasPermission('vendors.manage');
    }

    /**
     * Determine whether the user can view vendor wallet details.
     */
    public function viewWallet(User $user, Vendor $vendor): bool
    {
        return $user->id === $vendor->user_id || $user->hasPermission('vendors.view');
    }
}
