<?php

namespace App\Data\Parking;

final readonly class ReportParkingAvailabilityData
{
    public function __construct(
        public string $parkingIdentifier,
        public int $availableSpaces,
        public ?int $occupiedSpaces = null,
        public ?float $confidence = null,
    ) {}
}
