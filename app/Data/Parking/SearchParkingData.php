<?php

namespace App\Data\Parking;

use App\Enums\AvailabilityStatus;

final readonly class SearchParkingData
{
    public function __construct(
        public ?string $type = null,
        public ?AvailabilityStatus $availability = null,
        public ?int $providerId = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?int $radiusMeters = null,
        public ?string $sort = null,
        public int $perPage = 20,
        public int $page = 1,
    ) {}
}
