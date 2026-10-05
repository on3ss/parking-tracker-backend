<?php

namespace App\Actions\Parking;

use App\Enums\AvailabilityStatus;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

final class ApplyOccupancyToParking
{
    public function execute(
        ParkingFacility|StreetParking $parking,
        OccupancyReport $report,
    ): bool {

        $parking->update([
            'available_spaces' => $report->available_spaces,
            'availability_status' => AvailabilityStatus::for(
                capacity: $parking->capacity,
                availableSpaces: $report->available_spaces,
            ),
            'availability_updated_at' => $report->reported_at,
            'availability_source' => $report->source,
            'availability_report_id' => $report->id,
        ]);

        return true;
    }
}
