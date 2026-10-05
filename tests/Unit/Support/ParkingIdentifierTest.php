<?php

use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Support\Parking\ParkingIdentifier;

it('returns the facility public identifier', function () {
    $facility = new ParkingFacility;
    $facility->id = 42;

    expect(ParkingIdentifier::for($facility))
        ->toBe('facility:42');
});

it('returns the street parking public identifier', function () {
    $streetParking = new StreetParking;
    $streetParking->id = 17;

    expect(ParkingIdentifier::for($streetParking))
        ->toBe('street:17');
});

it('returns facility type', function () {
    $facility = new ParkingFacility;

    expect(ParkingIdentifier::type($facility))
        ->toBe('facility');
});

it('returns street parking type', function () {
    $streetParking = new StreetParking;

    expect(ParkingIdentifier::type($streetParking))
        ->toBe('street');
});
