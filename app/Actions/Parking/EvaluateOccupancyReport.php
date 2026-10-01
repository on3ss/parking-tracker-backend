<?php

namespace App\Actions\Parking;

use App\Enums\ParkingSource;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Carbon\CarbonInterface;

final class EvaluateOccupancyReport
{
    public function execute(
        ParkingFacility|StreetParking $parking,
        ParkingSource $source,
        CarbonInterface $reportedAt,
    ): bool {
        if (
            $parking->availability_source === null
            || $parking->availability_updated_at === null
        ) {
            return true;
        }

        if ($this->isStale($parking, $reportedAt)) {
            return true;
        }

        return $source->trustLevel()
            >= $parking->availability_source->trustLevel();
    }

    private function isStale(
        ParkingFacility|StreetParking $parking,
        CarbonInterface $at,
    ): bool {
        $expiresAt = $parking->availability_updated_at
            ->copy()
            ->addMinutes(
                $parking->availability_source->freshnessTtlMinutes(),
            );

        return $at->greaterThanOrEqualTo($expiresAt);
    }
}