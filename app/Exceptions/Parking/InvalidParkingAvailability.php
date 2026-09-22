<?php

namespace App\Exceptions\Parking;

use RuntimeException;

final class InvalidParkingAvailability extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }
}
