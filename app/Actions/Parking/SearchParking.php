<?php

namespace App\Actions\Parking;

use App\Data\Parking\ParkingSearchResult;
use App\Data\Parking\SearchParkingData;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SearchParking
{
    public function execute(SearchParkingData $data): LengthAwarePaginator
    {
        $queries = [];

        if ($data->type !== 'street') {
            $queries[] = $this->facilityQuery($data);
        }

        if ($data->type !== 'facility') {
            $queries[] = $this->streetQuery($data);
        }

        $union = array_shift($queries);

        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        $page = $this->sort(
            DB::query()->fromSub($union, 'results'),
            $data,
        )->paginate(
            perPage: $data->perPage,
            page: $data->page,
        );

        return $page->setCollection(
            $this->hydrate($page->getCollection()),
        );
    }

    /**
     * Lightweight projection: kind, id, name, capacity, available_spaces, distance_meters.
     */
    private function facilityQuery(SearchParkingData $data): BaseBuilder
    {
        $query = ParkingFacility::query()
            ->where('parking_facilities.status', 'ACTIVE')
            ->select([
                'parking_facilities.id',
                'parking_facilities.name',
                'parking_facilities.capacity',
                'parking_facilities.available_spaces',
            ])
            ->selectRaw("'facility' as kind");

        $this->applyFilters($query->getQuery(), 'parking_facilities', $data);

        if ($this->hasPoint($data)) {
            $distance = 'ST_DistanceSphere(ST_SetSRID(ST_MakePoint(?, ?), 4326), locations.coordinates::geometry)';

            $query
                ->join('locations', 'locations.id', '=', 'parking_facilities.location_id')
                ->selectRaw("$distance as distance_meters", [$data->longitude, $data->latitude]);

            if ($data->radiusMeters !== null) {
                $query->whereRaw("$distance <= ?", [$data->longitude, $data->latitude, $data->radiusMeters]);
            }
        } else {
            $query->selectRaw('NULL::float8 as distance_meters');
        }

        return $query->toBase(); // applies SoftDeletes scope
    }

    private function streetQuery(SearchParkingData $data): BaseBuilder
    {
        $query = StreetParking::query()
            ->where('street_parkings.status', 'ACTIVE')
            ->select([
                'street_parkings.id',
                'street_parkings.name',
                'street_parkings.capacity',
                'street_parkings.available_spaces',
            ])
            ->selectRaw("'street' as kind");

        $this->applyFilters($query->getQuery(), 'street_parkings', $data);

        if ($this->hasPoint($data)) {
            $distance = 'ST_DistanceSphere(ST_SetSRID(ST_MakePoint(?, ?), 4326), street_parkings.geometry)';

            $query->selectRaw("$distance as distance_meters", [$data->longitude, $data->latitude]);

            if ($data->radiusMeters !== null) {
                $query->whereRaw("$distance <= ?", [$data->longitude, $data->latitude, $data->radiusMeters]);
            }
        } else {
            $query->selectRaw('NULL::float8 as distance_meters');
        }

        return $query->toBase();
    }

    private function applyFilters(BaseBuilder $query, string $table, SearchParkingData $data): void
    {
        if ($data->availability !== null) {
            $query->where("$table.availability_status", $data->availability->value);
        }

        if ($data->providerId !== null) {
            $query->where("$table.parking_provider_id", $data->providerId);
        }
    }

    private function sort(BaseBuilder $query, SearchParkingData $data): BaseBuilder
    {
        $sort = $data->sort
            ?? ($this->hasPoint($data) ? 'distance' : 'name');

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $column = match (ltrim($sort, '-')) {
            'distance' => 'distance_meters',
            'capacity' => 'capacity',
            'available_spaces' => 'available_spaces',
            default => 'name',
        };

        // Whitelisted column + direction, safe for orderByRaw.
        return $query
            ->orderByRaw("$column $direction NULLS LAST")
            ->orderBy('kind')
            ->orderBy('id'); // deterministic pagination
    }

    /**
     * Load full models for the current page only (2 queries + eager loads).
     */
    private function hydrate(Collection $rows): Collection
    {
        $models = [
            'facility' => $this->loadModels(ParkingFacility::class, $rows, 'facility'),
            'street' => $this->loadModels(StreetParking::class, $rows, 'street'),
        ];

        return $rows
            ->map(function (object $row) use ($models) {
                $parking = $models[$row->kind][$row->id] ?? null;

                return $parking === null ? null : new ParkingSearchResult(
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
     * @param  class-string<Model>  $model
     */
    private function loadModels(string $model, Collection $rows, string $kind): Collection
    {
        $ids = $rows->where('kind', $kind)->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return $model::query()
            ->with(['provider', 'location'])
            ->whereKey($ids)
            ->get()
            ->keyBy('id');
    }

    private function hasPoint(SearchParkingData $data): bool
    {
        return $data->latitude !== null && $data->longitude !== null;
    }
}
