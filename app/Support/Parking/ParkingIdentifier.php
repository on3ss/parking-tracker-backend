<?php

namespace App\Support\Parking;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use InvalidArgumentException;

final class ParkingIdentifier
{
    public static function for(
        ParkingFacility|StreetParking $parking,
    ): string {
        $type = $parking instanceof ParkingFacility
            ? 'facility'
            : 'street';

        return "{$type}:{$parking->id}";
    }

    public static function type(
        ParkingFacility|StreetParking $parking,
    ): string {
        return match (true) {
            $parking instanceof ParkingFacility => 'facility',
            $parking instanceof StreetParking => 'street',

            default => throw new InvalidArgumentException(
                'Unsupported parking model.',
            ),
        };
    }
}
