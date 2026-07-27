<?php

namespace Modules\Vendor\Events;

use Modules\Vendor\Models\Vendor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Vendor $vendor
    ) {}
}
