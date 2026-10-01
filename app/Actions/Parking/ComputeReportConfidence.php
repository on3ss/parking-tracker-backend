<?php

namespace App\Actions\Parking;

use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Carbon\CarbonInterface;

final class ComputeReportConfidence
{
    /** Reports within this window can corroborate each other. */
    private const CORROBORATION_WINDOW_MINUTES = 10;

    /** |a - b| <= this counts as agreement. */
    private const AGREEMENT_TOLERANCE = 2;

    /** Cap on corroboration credit — 3 independent reports is enough. */
    private const MAX_CORROBORATORS = 3;

    public function execute(
        ParkingFacility|StreetParking $parking,
        ParkingSource $source,
        int $availableSpaces,
        ?float $claimedConfidence,
        CarbonInterface $reportedAt,
    ): float {
        $self = $this->selfAssessment($source, $claimedConfidence);
        $corroboration = $this->corroboration(
            $parking,
            $source,
            $availableSpaces,
            $reportedAt,
        );
        $recency = $this->recency($reportedAt);

        // Self is a hard ceiling; corroboration and recency only pull down.
        return round(
            $self * $corroboration * $recency,
            4,
        );
    }

    private function selfAssessment(
        ParkingSource $source,
        ?float $claimed,
    ): float {
        $ceiling = $source->confidenceCeiling();

        if ($claimed === null) {
            return $ceiling;
        }

        return min(max($claimed, 0.0), $ceiling);
    }

    private function corroboration(
        ParkingFacility|StreetParking $parking,
        ParkingSource $source,
        int $availableSpaces,
        CarbonInterface $reportedAt,
    ): float {
        $since = $reportedAt->copy()->subMinutes(
            self::CORROBORATION_WINDOW_MINUTES,
        );

        $agreers = OccupancyReport::query()
            ->where(function ($q) use ($parking) {
                $q->where('parking_facility_id', $parking->getKey())
                    ->orWhere('street_parking_id', $parking->getKey());
            })
            ->where('reported_at', '>=', $since)
            ->where('source', '!=', $source->value)
            ->whereNotNull('available_spaces')
            ->get(['available_spaces'])
            ->filter(
                fn($r) => abs($r->available_spaces - $availableSpaces)
                    <= self::AGREEMENT_TOLERANCE
            )
            ->count();

        // 0 corroborators → 1.0 (neutral), 3+ → 1.2 (bonus), never below 1.0.
        return 1.0 + (min($agreers, self::MAX_CORROBORATORS) / self::MAX_CORROBORATORS) * 0.2;
    }

    private function recency(CarbonInterface $reportedAt): float
    {
        $ageSeconds = max(0, $reportedAt->diffInSeconds(now()));

        // Full credit under 30s, linearly decaying to 0.5 at 10 minutes.
        if ($ageSeconds <= 30) {
            return 1.0;
        }

        $decay = min(1.0, ($ageSeconds - 30) / (600 - 30));

        return round(1.0 - ($decay * 0.5), 4);
    }
}