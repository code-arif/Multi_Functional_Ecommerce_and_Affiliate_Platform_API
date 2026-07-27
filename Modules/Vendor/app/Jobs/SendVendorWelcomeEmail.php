<?php

namespace Modules\Vendor\Jobs;

use Modules\Vendor\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendVendorWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Vendor $vendor
    ) {}

    public function handle(): void
    {
        // In production: Mail::to($this->vendor->email)->send(new VendorWelcomeMail($this->vendor));
        \Illuminate\Support\Facades\Log::info("Vendor welcome email queued for: {$this->vendor->shop_name}");
    }
}
