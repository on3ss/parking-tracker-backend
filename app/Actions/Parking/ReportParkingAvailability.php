<?php

namespace App\Actions\Parking;

use App\Data\Parking\ReportParkingAvailabilityData;
use App\Data\Parking\ReportParkingAvailabilityResult;
use App\Enums\ParkingSource;
use App\Exceptions\Parking\InvalidParkingAvailability;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Support\Facades\DB;

final class ReportParkingAvailability
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
        private RecordOccupancyObservation $recordOccupancyObservation,
        private ApplyOccupancyToParking $applyOccupancyToParking,
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

            $this->validateAvailability(
                parking: $parking,
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
                claimedConfidence: $data->confidence,
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

    private function validateAvailability(
        ParkingFacility|StreetParking $parking,
        int $availableSpaces,
        ?int $occupiedSpaces,
    ): void {
        if ($parking->capacity === null) {
            throw new InvalidParkingAvailability(
                field: 'available_spaces',
                message: 'Parking capacity must be set before availability can be reported.',
            );
        }

        if ($availableSpaces < 0) {
            throw new InvalidParkingAvailability(
                field: 'available_spaces',
                message: 'Available spaces cannot be negative.',
            );
        }

        if ($occupiedSpaces !== null && $occupiedSpaces < 0) {
            throw new InvalidParkingAvailability(
                field: 'occupied_spaces',
                message: 'Occupied spaces cannot be negative.',
            );
        }

        if ($availableSpaces > $parking->capacity) {
            throw new InvalidParkingAvailability(
                field: 'available_spaces',
                message: 'Available spaces cannot exceed parking capacity.',
            );
        }

        if (
            $occupiedSpaces !== null
            && $occupiedSpaces > $parking->capacity
        ) {
            throw new InvalidParkingAvailability(
                field: 'occupied_spaces',
                message: 'Occupied spaces cannot exceed parking capacity.',
            );
        }

        if (
            $occupiedSpaces !== null
            && $occupiedSpaces + $availableSpaces > $parking->capacity
        ) {
            throw new InvalidParkingAvailability(
                field: 'available_spaces',
                message: 'Occupied and available spaces cannot exceed parking capacity.',
            );
        }
    }
}
