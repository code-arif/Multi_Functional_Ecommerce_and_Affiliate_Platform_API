<?php

namespace Modules\Vendor\Listeners;

use Modules\Vendor\Events\VendorRegistered;
use Modules\Vendor\Events\VendorApproved;
use Modules\Vendor\Events\VendorRejected;
use Modules\Vendor\Jobs\SendVendorWelcomeEmail;
use Modules\Notifications\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendVendorNotification
{
    /**
     * Handle vendor lifecycle events.
     */
    public function handle(VendorRegistered|VendorApproved|VendorRejected $event): void
    {
        $vendor = $event->vendor;
        $user = $vendor->user;

        match ($event::class) {
            VendorRegistered::class => $this->handleRegistered($vendor, $user),
            VendorApproved::class   => $this->handleApproved($vendor, $user),
            VendorRejected::class   => $this->handleRejected($vendor, $user, $event->reason ?? ''),
            default                 => null,
        };
    }

    private function handleRegistered($vendor, $user): void
    {
        Log::info("Vendor registered: {$vendor->shop_name} (User: {$user->id})");

        // Create in-app notification for admin
        Notification::create([
            'type'         => 'vendor_registered',
            'notifiable_type' => get_class($user),
            'notifiable_id'   => 1, // Notify first admin (super admin)
            'data'         => [
                'message' => "New vendor registration: {$vendor->shop_name}",
                'vendor_id' => $vendor->id,
                'action_url' => "/admin/vendors/{$vendor->id}",
            ],
        ]);
    }

    private function handleApproved($vendor, $user): void
    {
        Log::info("Vendor approved: {$vendor->shop_name} (User: {$user->id})");

        // Create in-app notification for vendor
        Notification::create([
            'type'         => 'vendor_approved',
            'notifiable_type' => get_class($user),
            'notifiable_id'   => $user->id,
            'data'         => [
                'message' => "Congratulations! Your vendor shop '{$vendor->shop_name}' has been approved. You can now start selling.",
                'vendor_id' => $vendor->id,
                'action_url' => "/vendor/dashboard",
            ],
        ]);

        // Dispatch queued job for welcome email
        SendVendorWelcomeEmail::dispatch($vendor);
    }

    private function handleRejected($vendor, $user, string $reason): void
    {
        Log::info("Vendor rejected: {$vendor->shop_name} (Reason: {$reason})");

        // Create in-app notification for vendor
        Notification::create([
            'type'         => 'vendor_rejected',
            'notifiable_type' => get_class($user),
            'notifiable_id'   => $user->id,
            'data'         => [
                'message' => "Your vendor application for '{$vendor->shop_name}' was rejected. Reason: {$reason}",
                'vendor_id' => $vendor->id,
                'reason'    => $reason,
            ],
        ]);
    }
}
