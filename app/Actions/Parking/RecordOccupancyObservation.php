<?php

namespace App\Actions\Parking;

use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Carbon\CarbonInterface;

final class RecordOccupancyObservation
{
    public function execute(
        ParkingFacility|StreetParking $parking,
        ParkingSource $source,
        ?int $userId,
        int $availableSpaces,
        int $occupiedSpaces,
        CarbonInterface $reportedAt,
    ): OccupancyReport {
        return OccupancyReport::query()->create([
            'user_id' => $userId,
            'source' => $source,
            'occupied_spaces' => $occupiedSpaces,
            'available_spaces' => $availableSpaces,
            'reported_at' => $reportedAt,
            'parking_facility_id' => $parking instanceof ParkingFacility
                ? $parking->id
                : null,
            'street_parking_id' => $parking instanceof StreetParking
                ? $parking->id
                : null,
        ]);
    }
}
