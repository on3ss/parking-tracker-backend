<?php

use App\Models\Location;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;

it('returns nearby parking', function () {
    $facilityLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5779199,
            longitude: 91.8837004,
        ),
    ]);

    $facility = ParkingFacility::factory()->create([
        'location_id' => $facilityLocation->id,
    ]);

    $streetLocation = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: 25.5768,
            longitude: 91.8826,
        ),
    ]);

    $streetParking = StreetParking::factory()->create([
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

    $response = $this
        ->getJson(
            '/api/v1/parking/nearby'
            . '?latitude=25.5779199'
            . '&longitude=91.8837004'
            . '&radius=2000',
        )
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'name',
                    'distance' => [
                        'meters',
                        'kilometers',
                    ],
                    'provider',
                    'location',
                    'capacity',
                    'availability',
                ],
            ],
        ]);

    $parking = collect($response->json('data'));

    expect($parking)->not->toBeEmpty();

    expect(
        $parking->pluck('id'),
    )->toContain(
            "facility:{$facility->id}",
            "street:{$streetParking->id}",
        );

    $parking->each(function (array $item) {
        expect($item['id'])
            ->toMatch('/^(facility|street):\d+$/');

        expect($item['type'])
            ->toBeIn(['facility', 'street']);
    });
});