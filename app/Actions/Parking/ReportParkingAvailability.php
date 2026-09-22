<?php

namespace App\Actions\Parking;

use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Exceptions\Parking\InvalidParkingAvailability;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class ReportParkingAvailability
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
    ) {
    }

    public function execute(
        ReportParkingAvailabilityData $data,
        ParkingSource $source = ParkingSource::USER,
        ?int $userId = null,
    ): ParkingFacility|StreetParking {
        return DB::transaction(function () use ($data, $source, $userId) {
            /** @var ParkingFacility|StreetParking $parking */
            $parking = $this->resolveParkingIdentifier->execute(
                $data->parkingIdentifier,
            );

            $this->validateAvailability(
                parking: $parking,
                availableSpaces: $data->availableSpaces,
                occupiedSpaces: $data->occupiedSpaces,
            );

            $reportedAt = now();

            $occupiedSpaces = $data->occupiedSpaces
                ?? $parking->capacity - $data->availableSpaces;

            $this->createReport(
                parking: $parking,
                source: $source,
                userId: $userId,
                occupiedSpaces: $occupiedSpaces,
                availableSpaces: $data->availableSpaces,
                confidence: $data->confidence,
                reportedAt: $reportedAt,
            );

            $parking->update([
                'available_spaces' => $data->availableSpaces,
                'availability_status' => $this->availabilityStatus(
                    capacity: $parking->capacity,
                    availableSpaces: $data->availableSpaces,
                ),
                'availability_updated_at' => $reportedAt,
            ]);

            return $parking->fresh();
        });
    }

    private function validateAvailability(
        ParkingFacility|StreetParking $parking,
        int $availableSpaces,
        ?int $occupiedSpaces,
    ): void {
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

    private function createReport(
        ParkingFacility|StreetParking $parking,
        ParkingSource $source,
        ?int $userId,
        int $occupiedSpaces,
        int $availableSpaces,
        ?float $confidence,
        Carbon $reportedAt,
    ): OccupancyReport {
        return OccupancyReport::query()->create([
            'user_id' => $userId,
            'source' => $source,
            'occupied_spaces' => $occupiedSpaces,
            'available_spaces' => $availableSpaces,
            'confidence' => $confidence,
            'reported_at' => $reportedAt,
            'parking_facility_id' => $parking instanceof ParkingFacility
                ? $parking->id
                : null,
            'street_parking_id' => $parking instanceof StreetParking
                ? $parking->id
                : null,
        ]);
    }

    private function availabilityStatus(
        int $capacity,
        int $availableSpaces,
    ): AvailabilityStatus {
        if ($capacity === 0) {
            return AvailabilityStatus::UNKNOWN;
        }

        if ($availableSpaces === 0) {
            return AvailabilityStatus::FULL;
        }

        return $availableSpaces / $capacity <= 0.20
            ? AvailabilityStatus::LIMITED
            : AvailabilityStatus::AVAILABLE;
    }
}
