<?php

namespace Modules\Orders\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Bkash = 'bkash';
    case Nagad = 'nagad';
    case Sslcommerz = 'sslcommerz';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Cash on Delivery',
            self::Bkash => 'bKash',
            self::Nagad => 'Nagad',
            self::Sslcommerz => 'SSLCommerz',
            self::Card => 'Card',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
