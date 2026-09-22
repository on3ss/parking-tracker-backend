<?php

namespace App\Actions\Parking;

use App\Data\Parking\ParkingSearchResult;
use App\Data\Parking\SearchParkingData;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Database\Expressions\AsGeometry;
use Clickbar\Magellan\Database\PostgisFunctions\ST;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class SearchParking
{
    public function execute(
        SearchParkingData $data,
    ): LengthAwarePaginator {
        $point = $this->point($data);

        $results = collect();

        if ($data->type !== 'street') {
            $results = $results->merge(
                $this->searchFacilities(
                    $data,
                    $point,
                ),
            );
        }

        if ($data->type !== 'facility') {
            $results = $results->merge(
                $this->searchStreetParking(
                    $data,
                    $point,
                ),
            );
        }

        $results = $this->sortResults(
            $results,
            $data,
        );

        return $this->paginate(
            $results,
            $data,
        );
    }

    private function searchFacilities(
        SearchParkingData $data,
        ?Point $point,
    ): Collection {
        $query = QueryBuilder::for(
            ParkingFacility::query(),
        )
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
            );

        $this->applyFilters(
            $query,
            $data,
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
                ->select(
                    'parking_facilities.*',
                )
                ->addSelect(
                    $distance->as('distance_meters'),
                );

            if ($data->radiusMeters !== null) {
                $query->where(
                    $distance,
                    '<=',
                    $data->radiusMeters,
                );
            }
        }

        return $query
            ->get()
            ->map(
                fn (ParkingFacility $parking) => new ParkingSearchResult(
                    parking: $parking,
                    type: 'facility',
                    distanceMeters: isset(
                        $parking->distance_meters,
                    )
                    ? (float) $parking->distance_meters
                    : null,
                ),
            );
    }

    private function searchStreetParking(
        SearchParkingData $data,
        ?Point $point,
    ): Collection {
        $query = QueryBuilder::for(
            StreetParking::query(),
        )
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
            );

        $this->applyFilters(
            $query,
            $data,
        );

        if ($point !== null) {
            $distance = ST::distanceSphere(
                $point,
                'street_parkings.geometry',
            );

            $query
                ->select(
                    'street_parkings.*',
                )
                ->addSelect(
                    $distance->as('distance_meters'),
                );

            if ($data->radiusMeters !== null) {
                $query->where(
                    $distance,
                    '<=',
                    $data->radiusMeters,
                );
            }
        }

        return $query
            ->get()
            ->map(
                fn (StreetParking $parking) => new ParkingSearchResult(
                    parking: $parking,
                    type: 'street',
                    distanceMeters: isset(
                        $parking->distance_meters,
                    )
                    ? (float) $parking->distance_meters
                    : null,
                ),
            );
    }

    private function applyFilters(
        QueryBuilder $query,
        SearchParkingData $data,
    ): void {
        if ($data->availability !== null) {
            $query->where(
                'availability_status',
                $data->availability->value,
            );
        }

        if ($data->providerId !== null) {
            $query->where(
                'parking_provider_id',
                $data->providerId,
            );
        }
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
        $sort = $data->sort;

        if (
            $sort === null &&
            $data->latitude !== null &&
            $data->longitude !== null
        ) {
            return $results
                ->sortBy(
                    fn (ParkingSearchResult $result) => $result->distanceMeters,
                    SORT_NUMERIC,
                )
                ->values();
        }

        if ($sort === null) {
            return $results
                ->sortBy(
                    fn (ParkingSearchResult $result) => $result->parking->name,
                    SORT_NATURAL,
                )
                ->values();
        }

        $descending = str_starts_with(
            $sort,
            '-',
        );

        $field = ltrim(
            $sort,
            '-',
        );

        $sorted = match ($field) {
            'distance' => $results->sortBy(
                fn (ParkingSearchResult $result) => $result->distanceMeters,
                SORT_NUMERIC,
                $descending,
            ),

            'name' => $results->sortBy(
                fn (ParkingSearchResult $result) => $result->parking->name,
                SORT_NATURAL,
                $descending,
            ),

            'capacity' => $results->sortBy(
                fn (ParkingSearchResult $result) => $result->parking->capacity,
                SORT_NUMERIC,
                $descending,
            ),

            'available_spaces' => $results->sortBy(
                fn (ParkingSearchResult $result) => $result->parking->available_spaces,
                SORT_NUMERIC,
                $descending,
            ),

            default => $results,
        };

        return $sorted->values();
    }

    private function paginate(
        Collection $results,
        SearchParkingData $data,
    ): LengthAwarePaginator {
        $total = $results->count();

        $items = $results
            ->forPage(
                $data->page,
                $data->perPage,
            )
            ->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $data->perPage,
            currentPage: $data->page,
        );
    }
}
