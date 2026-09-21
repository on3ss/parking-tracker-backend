<?php

namespace App\Data\Parking;

final readonly class FindNearbyParkingData
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public int $radiusMeters = 2000,
        public int $limit = 50,
    ) {
    }
}