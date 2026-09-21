<?php

use App\Actions\Parking\FindNearbyParking;
use App\Data\Parking\FindNearbyParkingData;
use App\Models\Location;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;

it('finds facilities and street parking nearby', function () {
    $facilityLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5779199,
            longitude: 91.8837004,
        ),
    ]);

    ParkingFacility::factory()->create([
        'location_id' => $facilityLocation->id,
    ]);

    $streetLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5768,
            longitude: 91.8826,
        ),
    ]);

    StreetParking::factory()->create([
        'location_id' => $streetLocation->id,
        'geometry' => LineString::make([
            Point::makeGeodetic(
                latitude: 25.5767,
                longitude: 91.8824,
            ),
            Point::makeGeodetic(
                latitude: 25.5769,
                longitude: 91.8828,
            ),
        ]),
    ]);

    $results = app(FindNearbyParking::class)->execute(
        new FindNearbyParkingData(
            latitude: 25.5779199,
            longitude: 91.8837004,
            radiusMeters: 2000,
        ),
    );

    expect($results)->not->toBeEmpty();

    expect(
        $results->contains(
            fn ($result) => $result->type === 'facility',
        ),
    )->toBeTrue();

    expect(
        $results->contains(
            fn ($result) => $result->type === 'street',
        ),
    )->toBeTrue();
});
