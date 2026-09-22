<?php

namespace App\Data\Parking;

final readonly class GetParkingAvailabilityHistoryData
{
    public function __construct(
        public string $parkingIdentifier,
        public int $perPage = 20,
        public int $page = 1,
    ) {}
}
