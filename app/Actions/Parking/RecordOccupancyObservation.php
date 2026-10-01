<?php

namespace App\Actions\Parking;

use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Carbon\CarbonInterface;

final class RecordOccupancyObservation
{
    public function __construct(
        private ComputeReportConfidence $computeConfidence,
    ) {
    }

    public function execute(
        ParkingFacility|StreetParking $parking,
        ParkingSource $source,
        ?int $userId,
        int $availableSpaces,
        int $occupiedSpaces,
        ?float $claimedConfidence,
        CarbonInterface $reportedAt,
    ): OccupancyReport {
        $computedConfidence = $this->computeConfidence->execute(
            parking: $parking,
            source: $source,
            availableSpaces: $availableSpaces,
            claimedConfidence: $claimedConfidence,
            reportedAt: $reportedAt,
        );

        return OccupancyReport::query()->create([
            'user_id' => $userId,
            'source' => $source,
            'occupied_spaces' => $occupiedSpaces,
            'available_spaces' => $availableSpaces,
            'reported_confidence' => $claimedConfidence,
            'computed_confidence' => $computedConfidence,
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