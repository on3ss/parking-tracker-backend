<?php

namespace App\Models\Concerns;

use App\Support\Parking\AvailabilityFreshness;
use Illuminate\Database\Eloquent\Builder;

trait HasStaleAvailability
{
    /**
     * Records whose last availability update is older than the freshness
     * threshold. Records that have never been updated are excluded.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStaleAvailability(Builder $query): Builder
    {
        return $query
            ->whereNotNull('availability_updated_at')
            ->where(
                'availability_updated_at',
                '<',
                AvailabilityFreshness::cutoff(),
            );
    }

    /**
     * Records that have never been updated or whose last update is stale.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStaleOrNeverReportedAvailability(
        Builder $query,
    ): Builder {
        $cutoff = AvailabilityFreshness::cutoff();

        return $query->where(function (Builder $query) use ($cutoff) {
            $query
                ->whereNull('availability_updated_at')
                ->orWhere('availability_updated_at', '<', $cutoff);
        });
    }
}
