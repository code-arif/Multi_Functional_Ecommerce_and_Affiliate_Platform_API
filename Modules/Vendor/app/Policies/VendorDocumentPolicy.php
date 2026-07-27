<?php

namespace Modules\Vendor\Policies;

use Modules\Auth\Models\User;
use Modules\Vendor\Models\VendorDocument;

class VendorDocumentPolicy
{
    /**
     * Determine whether the user can view their own documents.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a document.
     */
    public function view(User $user, VendorDocument $document): bool
    {
        // Vendor owner can view their own documents
        if ($user->id === $document->vendor->user_id) {
            return true;
        }

        // Admin with permission can view
        return $user->hasPermission('vendors.view');
    }

    /**
     * Determine whether the user can upload documents for their vendor.
     */
    public function create(User $user): bool
    {
        return $user->vendor()->exists();
    }

    /**
     * Admin: verify a document.
     */
    public function verify(User $user): bool
    {
        return $user->hasPermission('vendors.manage');
    }

    /**
     * Admin: reject a document.
     */
    public function reject(User $user): bool
    {
        return $user->hasPermission('vendors.manage');
    }
}
