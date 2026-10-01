<?php

namespace App\Data\Parking;

use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

final readonly class ReportParkingAvailabilityResult
{
    public function __construct(
        public ParkingFacility|StreetParking $parking,
        public OccupancyReport $report,
        public bool $accepted,
    ) {
    }
}