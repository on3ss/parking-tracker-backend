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
        float $computedConfidence,
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

        $currentTrust = $parking->availability_source->trustLevel();
        $incomingTrust = $source->trustLevel();

        // Higher trust always wins over a fresh incumbent.
        if ($incomingTrust > $currentTrust) {
            return true;
        }

        // Lower trust never wins over a fresh incumbent.
        if ($incomingTrust < $currentTrust) {
            return false;
        }

        // Same tier: compare computed confidence.
        $currentConfidence = (float) (
            $parking->availabilityReport?->computed_confidence ?? 0.0
        );

        return $computedConfidence > $currentConfidence;
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
