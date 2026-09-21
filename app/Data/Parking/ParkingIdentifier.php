<?php

namespace App\Data\Parking;

use App\Models\ParkingFacility;
use App\Models\StreetParking;

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
}