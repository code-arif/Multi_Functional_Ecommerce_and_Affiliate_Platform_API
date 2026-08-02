<?php

namespace Modules\Vendor\Emails;

use Modules\Vendor\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VendorWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Vendor $vendor,
        public ?string $password = null
    ) {
        // Mailable::$html (inherited) is used as the raw inline body
        $this->html = $this->buildBody();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your vendor account has been created',
        );
    }

    private function buildBody(): string
    {
        $name  = $this->vendor->user?->name ?? $this->vendor->shop_name;
        $email = $this->vendor->user?->email ?? $this->vendor->email;

        $lines = [
            "Hi {$name},",
            '',
            "Your vendor account for '{$this->vendor->shop_name}' has been created successfully.",
            '',
            'Login credentials:',
            "Email: {$email}",
        ];

        if ($this->password) {
            $lines[] = "Password: {$this->password}";
        }

        $lines[] = '';
        $lines[] = 'Your account is currently pending approval. You will be able to log in and start selling once an admin activates it.';
        $lines[] = '';
        $lines[] = 'Thanks,';
        $lines[] = config('ecommerce.store_name', 'EcoShop');

        return implode(PHP_EOL, $lines);
    }
}
