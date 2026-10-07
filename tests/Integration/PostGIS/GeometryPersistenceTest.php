<?php

use App\Models\Location;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;

it('persists and retrieves a location point', function () {
    $latitude = 25.5788;
    $longitude = 91.8933;

    $location = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: $latitude,
            longitude: $longitude,
        ),
    ]);

    $location->refresh();

    expect($location->coordinates)
        ->toBeInstanceOf(Point::class);

    expect($location->coordinates->getLatitude())
        ->toBe($latitude);

    expect($location->coordinates->getLongitude())
        ->toBe($longitude);
});

it('persists and retrieves street parking geometry', function () {
    $geometry = LineString::make([
        Point::makeGeodetic(
            latitude: 25.5788,
            longitude: 91.8933,
        ),
        Point::makeGeodetic(
            latitude: 25.5790,
            longitude: 91.8935,
        ),
    ]);

    $streetParking = StreetParking::factory()->create([
        'geometry' => $geometry,
    ]);

    $streetParking->refresh();

    expect($streetParking->geometry)
        ->toBeInstanceOf(LineString::class);
});

it('preserves the location point after a street parking reload', function () {
    $latitude = 25.5788;
    $longitude = 91.8933;

    $location = Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: $latitude,
            longitude: $longitude,
        ),
    ]);

    $streetParking = StreetParking::factory()->create([
        'location_id' => $location->id,
    ]);

    $streetParking->load('location');

    expect($streetParking->location->coordinates)
        ->toBeInstanceOf(Point::class);

    expect($streetParking->location->coordinates->getLatitude())
        ->toBe($latitude);

    expect($streetParking->location->coordinates->getLongitude())
        ->toBe($longitude);
});
