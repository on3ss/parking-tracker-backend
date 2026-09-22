<?php

namespace App\Data\Parking;

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Support\Parking\ParkingIdentifier;

final readonly class ParkingSearchResult
{
    public function __construct(
        public ParkingFacility|StreetParking $parking,
        public string $type,
        public ?float $distanceMeters,
    ) {
    }

    public function publicId(): string
    {
        return ParkingIdentifier::for($this->parking);
    }
}
