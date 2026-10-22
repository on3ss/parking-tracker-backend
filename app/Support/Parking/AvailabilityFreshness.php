<?php

namespace App\Support\Parking;

use Illuminate\Support\Carbon;

final class AvailabilityFreshness
{
    public const STALE_AFTER_MINUTES = 60;

    public static function cutoff(): Carbon
    {
        $minutes = (int) config(
            'parking.availability_stale_after_minutes',
            self::STALE_AFTER_MINUTES,
        );

        return now()->subMinutes($minutes);
    }
}
