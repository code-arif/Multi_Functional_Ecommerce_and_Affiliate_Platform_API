<?php

namespace Modules\Orders\Enums;

/**
 * The single source of truth for order lifecycle.
 *
 * pending → confirmed → processing → shipped → delivered
 *     └→ cancelled   └→ cancelled    └→ cancelled    └→ refunded
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * @return string[] All raw values (for validation rules).
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string> value => label map.
     */
    public static function labels(): array
    {
        $map = [];
        foreach (self::cases() as $case) {
            $map[$case->value] = $case->label();
        }

        return $map;
    }

    public static function fromValue(?string $value): ?self
    {
        if (!$value) {
            return null;
        }

        return self::tryFrom($value);
    }

    /**
     * Allowed next states for a given state.
     *
     * @return OrderStatus[]
     */
    public static function transitionsFor(OrderStatus $from): array
    {
        return match ($from) {
            self::Pending => [self::Confirmed, self::Processing, self::Cancelled],
            self::Confirmed => [self::Processing, self::Shipped, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Delivered, self::Refunded],
            self::Delivered => [self::Refunded],
            self::Cancelled => [],
            self::Refunded => [],
        };
    }

    public function canTransitionTo(OrderStatus $to): bool
    {
        return $this === $to || in_array($to, self::transitionsFor($this), true);
    }

    /**
     * Statuses from which an order can still be cancelled.
     *
     * @return OrderStatus[]
     */
    public static function cancelableStatuses(): array
    {
        return [self::Pending, self::Confirmed, self::Processing];
    }

    /**
     * Statuses from which a *customer* may cancel / request cancellation.
     *
     * @return OrderStatus[]
     */
    public static function customerCancelableStatuses(): array
    {
        return [self::Pending, self::Confirmed];
    }

    public function isCancelable(): bool
    {
        return in_array($this, self::cancelableStatuses(), true);
    }

    public function isCustomerCancelable(): bool
    {
        return in_array($this, self::customerCancelableStatuses(), true);
    }

    /**
     * Statuses counted as live revenue (excludes cancelled / refunded).
     *
     * @return string[]
     */
    public static function liveStatusValues(): array
    {
        return [
            self::Pending->value,
            self::Confirmed->value,
            self::Processing->value,
            self::Shipped->value,
            self::Delivered->value,
        ];
    }
}
