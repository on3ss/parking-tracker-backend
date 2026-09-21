<?php

namespace App\Actions\Parking;

use App\Data\Parking\ParkingSearchResult;
use App\Data\Parking\SearchParkingData;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Database\Expressions\AsGeometry;
use Clickbar\Magellan\Database\PostgisFunctions\ST;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

final class SearchParking
{
    public function execute(SearchParkingData $data): Collection
    {
        $point = $this->point($data);

        $results = collect();

        if ($data->type !== 'street') {
            $results = $results->merge(
                $this->searchFacilities($data, $point),
            );
        }

        if ($data->type !== 'facility') {
            $results = $results->merge(
                $this->searchStreetParking($data, $point),
            );
        }

        return $this->sortResults($results, $data);
    }

    private function searchFacilities(
        SearchParkingData $data,
        ?Point $point,
    ): Collection {
        $query = QueryBuilder::for(ParkingFacility::query())
            ->where('status', 'ACTIVE')
            ->with([
                'provider',
                'location',
            ])
            ->allowedFilters(
                AllowedFilter::exact(
                    'availability',
                    'availability_status',
                ),
                AllowedFilter::exact(
                    'provider_id',
                    'parking_provider_id',
                ),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('capacity'),
                AllowedSort::field(
                    'available_spaces',
                ),
            );

        if ($point !== null) {
            $distance = ST::distanceSphere(
                $point,
                new AsGeometry('locations.coordinates'),
            );
            $query
                ->join(
                    'locations',
                    'locations.id',
                    '=',
                    'parking_facilities.location_id',
                )
                ->select('parking_facilities.*')
                ->addSelect($distance->as('distance_meters'));

            if ($data->radiusMeters !== null) {
                $query->where(
                    $distance,
                    '<=',
                    $data->radiusMeters,
                );
            }
        }

        return $query->get()->map(
            fn(ParkingFacility $parking) => new ParkingSearchResult(
                parking: $parking,
                type: 'facility',
                distanceMeters: (float) (
                    $parking->distance_meters ?? 0
                ),
            ),
        );
    }

    private function searchStreetParking(
        SearchParkingData $data,
        ?Point $point,
    ): Collection {
        $query = QueryBuilder::for(StreetParking::query())
            ->where('status', 'ACTIVE')
            ->with([
                'provider',
                'location',
            ])
            ->allowedFilters(
                AllowedFilter::exact(
                    'availability',
                    'availability_status',
                ),
                AllowedFilter::exact(
                    'provider_id',
                    'parking_provider_id',
                ),
            )
            ->allowedSorts(
                AllowedSort::field('name'),
                AllowedSort::field('capacity'),
                AllowedSort::field(
                    'available_spaces',
                ),
            );

        if ($point !== null) {
            $distance = ST::distanceSphere(
                $point,
                new AsGeometry('locations.coordinates'),
            );
            $query
                ->select('street_parkings.*')
                ->addSelect($distance->as('distance_meters'));

            if ($data->radiusMeters !== null) {
                $query->where(
                    $distance,
                    '<=',
                    $data->radiusMeters,
                );
            }
        }

        return $query->get()->map(
            fn(StreetParking $parking) => new ParkingSearchResult(
                parking: $parking,
                type: 'street',
                distanceMeters: (float) (
                    $parking->distance_meters ?? 0
                ),
            ),
        );
    }

    private function point(
        SearchParkingData $data,
    ): ?Point {
        if (
            $data->latitude === null ||
            $data->longitude === null
        ) {
            return null;
        }

        return Point::makeGeodetic(
            latitude: $data->latitude,
            longitude: $data->longitude,
        );
    }

    private function sortResults(
        Collection $results,
        SearchParkingData $data,
    ): Collection {
        $sort = request()->query('sort');

        if ($sort === null) {
            if (
                $data->latitude !== null &&
                $data->longitude !== null
            ) {
                return $results
                    ->sortBy('distanceMeters')
                    ->values()
                    ->take($data->perPage);
            }

            return $results
                ->sortBy('parking.name')
                ->values()
                ->take($data->perPage);
        }

        $descending = str_starts_with($sort, '-');
        $field = ltrim($sort, '-');

        $results = match ($field) {
            'distance' => $results->sortBy(
                'distanceMeters',
                SORT_REGULAR,
                $descending,
            ),

            'name' => $results->sortBy(
                fn(ParkingSearchResult $result) =>
                    $result->parking->name,
                SORT_NATURAL,
                $descending,
            ),

            'capacity' => $results->sortBy(
                fn(ParkingSearchResult $result) =>
                    $result->parking->capacity,
                SORT_NUMERIC,
                $descending,
            ),

            'available_spaces' => $results->sortBy(
                fn(ParkingSearchResult $result) =>
                    $result->parking->available_spaces,
                SORT_NUMERIC,
                $descending,
            ),

            default => $results,
        };

        return $results
            ->values()
            ->take($data->perPage);
    }
}