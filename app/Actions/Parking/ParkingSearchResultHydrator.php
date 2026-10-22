<?php

namespace App\Actions\Parking;

use App\Data\Parking\ParkingSearchResult;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class ParkingSearchResultHydrator
{
    public function hydrate(Collection $rows): Collection
    {
        $models = [
            'facility' => $this->loadModels(
                ParkingFacility::class,
                $rows,
                'facility',
            ),
            'street' => $this->loadModels(
                StreetParking::class,
                $rows,
                'street',
            ),
        ];

        return $rows
            ->map(function (object $row) use ($models) {
                $parking = $models[$row->kind][$row->id] ?? null;

                if ($parking === null) {
                    return null;
                }

                return new ParkingSearchResult(
                    parking: $parking,
                    distanceMeters: $row->distance_meters !== null
                    ? (float) $row->distance_meters
                    : null,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @param class-string<Model> $model
     */
    private function loadModels(
        string $model,
        Collection $rows,
        string $kind,
    ): Collection {
        $ids = $rows
            ->where('kind', $kind)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return $model::query()
            ->with(['provider', 'location'])
            ->whereKey($ids)
            ->get()
            ->keyBy('id');
    }
}
