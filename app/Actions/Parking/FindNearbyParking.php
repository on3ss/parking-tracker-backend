<?php

namespace App\Actions\Parking;

use App\Data\Parking\FindNearbyParkingData;
use App\Data\Parking\SearchParkingData;
use Illuminate\Support\Collection;

final class FindNearbyParking
{
    public function __construct(
        private SearchParking $searchParking,
    ) {
    }

    public function execute(
        FindNearbyParkingData $data,
    ): Collection {
        return $this->searchParking->execute(
            new SearchParkingData(
                latitude: $data->latitude,
                longitude: $data->longitude,
                radiusMeters: $data->radiusMeters,
                sort: 'distance',
                perPage: $data->limit,
            ),
        );
    }
}