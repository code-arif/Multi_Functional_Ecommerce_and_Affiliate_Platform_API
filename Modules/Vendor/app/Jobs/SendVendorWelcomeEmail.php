<?php

namespace Modules\Vendor\Jobs;

use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Mail\VendorWelcomeMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVendorWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Vendor $vendor
    ) {}

    public function handle(): void
    {
        Mail::to($this->vendor->email)->send(new VendorWelcomeMail($this->vendor));
    }
}
