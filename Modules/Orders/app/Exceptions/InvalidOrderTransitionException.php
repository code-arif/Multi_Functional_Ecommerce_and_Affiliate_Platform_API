<?php

namespace Modules\Orders\Exceptions;

use RuntimeException;

/**
 * Thrown when an order status transition is not allowed by the state machine.
 */
class InvalidOrderTransitionException extends RuntimeException
{
    public static function invalidTarget(string $status): self
    {
        return new self("Invalid order status '{$status}'.");
    }

    public static function fromTo(string $from, string $to): self
    {
        return new self("Order cannot move from '{$from}' to '{$to}'.");
    }
}
