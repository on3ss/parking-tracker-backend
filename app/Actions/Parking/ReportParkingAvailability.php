<?php

namespace App\Actions\Parking;

use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReportParkingAvailability
{
    public function __construct(
        private ResolveParkingIdentifier $resolveParkingIdentifier,
    ) {}

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
            throw new InvalidArgumentException(
                'Available spaces cannot exceed parking capacity.',
            );
        }

        if (
            $occupiedSpaces !== null
            && $occupiedSpaces > $parking->capacity
        ) {
            throw new InvalidArgumentException(
                'Occupied spaces cannot exceed parking capacity.',
            );
        }

        if (
            $occupiedSpaces !== null
            && $occupiedSpaces + $availableSpaces > $parking->capacity
        ) {
            throw new InvalidArgumentException(
                'Occupied and available spaces cannot exceed parking capacity.',
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
        $reportedAt,
    ): OccupancyReport {
        $attributes = [
            'user_id' => $userId,
            'source' => $source,
            'occupied_spaces' => $occupiedSpaces,
            'available_spaces' => $availableSpaces,
            'confidence' => $confidence,
            'reported_at' => $reportedAt,
        ];

        if ($parking instanceof ParkingFacility) {
            $attributes['parking_facility_id'] = $parking->id;
            $attributes['street_parking_id'] = null;
        } else {
            $attributes['parking_facility_id'] = null;
            $attributes['street_parking_id'] = $parking->id;
        }

        return OccupancyReport::query()->create($attributes);
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
