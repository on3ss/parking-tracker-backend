<?php

namespace App\Actions\Parking;

use App\Data\Parking\FindNearbyParkingData;
use App\Data\Parking\ParkingSearchResult;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\Point;
use Clickbar\Magellan\Database\Expressions\AsGeometry;
use Clickbar\Magellan\Database\PostgisFunctions\ST;
use Illuminate\Support\Collection;

final class FindNearbyParking
{
    public function execute(
        FindNearbyParkingData $data,
    ): Collection {
        $point = Point::makeGeodetic(
            latitude: $data->latitude,
            longitude: $data->longitude,
        );

        $facilities = $this->findFacilities(
            point: $point,
            radiusMeters: $data->radiusMeters,
            limit: $data->limit,
        );

        $streetParking = $this->findStreetParking(
            point: $point,
            radiusMeters: $data->radiusMeters,
            limit: $data->limit,
        );

        return $facilities
            ->merge($streetParking)
            ->sortBy('distanceMeters')
            ->take($data->limit)
            ->values();
    }

    private function findFacilities(
        Point $point,
        int $radiusMeters,
        int $limit,
    ): Collection {
        $distance = ST::distanceSphere(
            $point,
            new AsGeometry('locations.coordinates'),
        );

        return ParkingFacility::query()
            ->select('parking_facilities.*')
            ->join(
                'locations',
                'locations.id',
                '=',
                'parking_facilities.location_id',
            )
            ->with([
                'location',
                'provider',
            ])
            ->addSelect(
                $distance->as('distance_meters'),
            )
            ->where(
                $distance,
                '<=',
                $radiusMeters,
            )
            ->where('parking_facilities.status', 'ACTIVE')
            ->orderBy($distance)
            ->limit($limit)
            ->get()
            ->map(
                fn (ParkingFacility $parking) => new ParkingSearchResult(
                    parking: $parking,
                    type: 'facility',
                    distanceMeters: (float) $parking->distance_meters,
                ),
            );
    }

    private function findStreetParking(
        Point $point,
        int $radiusMeters,
        int $limit,
    ): Collection {
        $distance = ST::distanceSphere(
            $point,
            'street_parkings.geometry',
        );

        return StreetParking::query()
            ->select('street_parkings.*')
            ->with([
                'location',
                'provider',
            ])
            ->addSelect(
                $distance->as('distance_meters'),
            )
            ->where(
                $distance,
                '<=',
                $radiusMeters,
            )
            ->where('street_parkings.status', 'ACTIVE')
            ->orderBy($distance)
            ->limit($limit)
            ->get()
            ->map(
                fn (StreetParking $parking) => new ParkingSearchResult(
                    parking: $parking,
                    type: 'street',
                    distanceMeters: (float) $parking->distance_meters,
                ),
            );
    }
}
