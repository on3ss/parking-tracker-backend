<?php

namespace App\Actions\Parking;

use App\Data\Parking\SearchParkingData;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Facades\DB;

final class ParkingSearchQueryBuilder
{
    public function build(SearchParkingData $data): BaseBuilder
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

        return $this->sort(
            DB::query()->fromSub($union, 'results'),
            $data,
        );
    }

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

        $this->applyFilters(
            $query->getQuery(),
            'parking_facilities',
            $data,
        );

        if ($this->hasPoint($data)) {
            $distance = 'ST_DistanceSphere(
                ST_SetSRID(ST_MakePoint(?, ?), 4326),
                locations.coordinates::geometry
            )';

            $query
                ->join(
                    'locations',
                    'locations.id',
                    '=',
                    'parking_facilities.location_id',
                )
                ->selectRaw(
                    "$distance as distance_meters",
                    [$data->longitude, $data->latitude],
                );

            if ($data->radiusMeters !== null) {
                $query->whereRaw(
                    "$distance <= ?",
                    [
                        $data->longitude,
                        $data->latitude,
                        $data->radiusMeters,
                    ],
                );
            }
        } else {
            $query->selectRaw('NULL::float8 as distance_meters');
        }

        return $query->toBase();
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

        $this->applyFilters(
            $query->getQuery(),
            'street_parkings',
            $data,
        );

        if ($this->hasPoint($data)) {
            $distance = 'ST_DistanceSphere(
                ST_SetSRID(ST_MakePoint(?, ?), 4326),
                street_parkings.geometry
            )';

            $query->selectRaw(
                "$distance as distance_meters",
                [$data->longitude, $data->latitude],
            );

            if ($data->radiusMeters !== null) {
                $query->whereRaw(
                    "$distance <= ?",
                    [
                        $data->longitude,
                        $data->latitude,
                        $data->radiusMeters,
                    ],
                );
            }
        } else {
            $query->selectRaw('NULL::float8 as distance_meters');
        }

        return $query->toBase();
    }

    private function applyFilters(
        BaseBuilder $query,
        string $table,
        SearchParkingData $data,
    ): void {
        if ($data->availability !== null) {
            $query->where(
                "$table.availability_status",
                $data->availability->value,
            );
        }

        if ($data->providerId !== null) {
            $query->where(
                "$table.parking_provider_id",
                $data->providerId,
            );
        }
    }

    private function sort(
        BaseBuilder $query,
        SearchParkingData $data,
    ): BaseBuilder {
        $sort = $data->sort
            ?? ($this->hasPoint($data) ? 'distance' : 'name');

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';

        $column = match (ltrim($sort, '-')) {
            'distance' => 'distance_meters',
            'capacity' => 'capacity',
            'available_spaces' => 'available_spaces',
            default => 'name',
        };

        // Both values are selected from explicit allowlists.
        return $query
            ->orderByRaw("$column $direction NULLS LAST")
            ->orderBy('kind')
            ->orderBy('id');
    }

    private function hasPoint(SearchParkingData $data): bool
    {
        return $data->latitude !== null
            && $data->longitude !== null;
    }
}
