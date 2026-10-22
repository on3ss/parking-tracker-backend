<?php

namespace App\Support\Parking;

use App\Exceptions\Parking\InvalidParkingAvailability;

final class AvailabilityReportRules
{
    public function validate(
        ?int $capacity,
        int $availableSpaces,
        ?int $occupiedSpaces,
    ): void {
        if ($capacity === null) {
            throw new InvalidParkingAvailability(
                field: 'available_spaces',
                message: 'Parking capacity must be set before availability can be reported.',
            );
        }

        if ($capacity < 0) {
            throw new InvalidParkingAvailability(
                field: 'capacity',
                message: 'Parking capacity cannot be negative.',
            );
        }

        if ($availableSpaces < 0 || $availableSpaces > $capacity) {
            throw new InvalidParkingAvailability(
                field: 'available_spaces',
                message: 'Available spaces must be between zero and capacity.',
            );
        }

        if ($occupiedSpaces === null) {
            return;
        }

        if ($occupiedSpaces < 0 || $occupiedSpaces > $capacity) {
            throw new InvalidParkingAvailability(
                field: 'occupied_spaces',
                message: 'Occupied spaces must be between zero and capacity.',
            );
        }

        if ($occupiedSpaces + $availableSpaces > $capacity) {
            throw new InvalidParkingAvailability(
                field: 'available_spaces',
                message: 'Occupied and available spaces cannot exceed parking capacity.',
            );
        }
    }
}
