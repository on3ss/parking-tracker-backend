<?php

namespace App\Actions\Parking;

use App\Data\Parking\ReportParkingAvailabilityData;
use App\Data\Parking\ReportParkingAvailabilityResult;
use App\Enums\ParkingSource;
use App\Support\Parking\AvailabilityReportRules;
use Illuminate\Support\Facades\DB;

final class ReportParkingAvailability
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
        private RecordOccupancyObservation $recordOccupancyObservation,
        private ApplyOccupancyToParking $applyOccupancyToParking,
        private AvailabilityReportRules $availabilityReportRules,
    ) {}

    public function execute(
        ReportParkingAvailabilityData $data,
        ParkingSource $source = ParkingSource::USER,
        ?int $userId = null,
    ): ReportParkingAvailabilityResult {
        return DB::transaction(function () use ($data, $source, $userId) {
            $parking = $this->resolveParkingIdentifier->execute(
                $data->parkingIdentifier,
                lockForUpdate: true,
            );

            $this->availabilityReportRules->validate(
                capacity: $parking->capacity,
                availableSpaces: $data->availableSpaces,
                occupiedSpaces: $data->occupiedSpaces,
            );

            $reportedAt = now();

            $report = $this->recordOccupancyObservation->execute(
                parking: $parking,
                source: $source,
                userId: $userId,
                availableSpaces: $data->availableSpaces,
                occupiedSpaces: $data->occupiedSpaces
                ?? $parking->capacity - $data->availableSpaces,
                reportedAt: $reportedAt,
            );

            $accepted = $this->applyOccupancyToParking->execute(
                parking: $parking,
                report: $report,
            );

            return new ReportParkingAvailabilityResult(
                parking: $parking->refresh(),
                report: $report,
                accepted: $accepted,
            );
        });
    }
}
