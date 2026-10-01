<?php

namespace App\Actions\Parking;

use App\Actions\Parking\EvaluateOccupancyReport;
use App\Actions\Parking\ResolveParkingIdentifier;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Data\Parking\ReportParkingAvailabilityResult;
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
        private EvaluateOccupancyReport $evaluateOccupancyReport,
        private ComputeReportConfidence $computeConfidence
    ) {
    }

    public function execute(
        ReportParkingAvailabilityData $data,
        ParkingSource $source = ParkingSource::USER,
        ?int $userId = null,
    ): ReportParkingAvailabilityResult {
        return DB::transaction(function () use ($data, $source, $userId) {
            $parking = $this->resolveParkingIdentifier->execute(
                $data->parkingIdentifier,
                lockForUpdate: true,   // ← everything below runs under the lock
            );

            $this->validateAvailability(
                parking: $parking,
                availableSpaces: $data->availableSpaces,
                occupiedSpaces: $data->occupiedSpaces,
            );

            $reportedAt = now();

            $occupiedSpaces = $data->occupiedSpaces
                ?? $parking->capacity - $data->availableSpaces;

            $computedConfidence = $this->computeConfidence->execute(
                parking: $parking,
                source: $source,
                availableSpaces: $data->availableSpaces,
                claimedConfidence: $data->confidence,
                reportedAt: $reportedAt,
            );

            $report = $this->createReport(
                parking: $parking,
                source: $source,
                userId: $userId,
                occupiedSpaces: $occupiedSpaces,
                availableSpaces: $data->availableSpaces,
                confidence: $data->confidence,
                reportedAt: $reportedAt,
            );

            $accepted = $this->evaluateOccupancyReport->execute(
                parking: $parking,
                source: $source,
                computedConfidence: $computedConfidence,
                reportedAt: $reportedAt,
            );

            if ($accepted) {
                $parking->update([
                    'available_spaces' => $data->availableSpaces,
                    'availability_status' => $this->availabilityStatus(
                        capacity: $parking->capacity,
                        availableSpaces: $data->availableSpaces,
                    ),
                    'availability_updated_at' => $reportedAt,
                    'availability_source' => $source,
                    'availability_report_id' => $report->id,
                ]);
            }

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

    private function createReport(
        ParkingFacility|StreetParking $parking,
        ParkingSource $source,
        ?int $userId,
        int $occupiedSpaces,
        int $availableSpaces,
        ?float $confidence,
        Carbon $reportedAt,
    ): OccupancyReport {
        $computedConfidence = $this->computeConfidence->execute(
            parking: $parking,
            source: $source,
            availableSpaces: $availableSpaces,
            claimedConfidence: $confidence,
            reportedAt: $reportedAt,
        );

        return OccupancyReport::query()->create([
            'user_id' => $userId,
            'source' => $source,
            'occupied_spaces' => $occupiedSpaces,
            'available_spaces' => $availableSpaces,
            'reported_confidence' => $confidence,
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
