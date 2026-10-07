<?php

use App\Actions\Parking\SearchParking;
use App\Data\Parking\SearchParkingData;
use App\Enums\AvailabilityStatus;
use App\Models\Location;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Clickbar\Magellan\Data\Geometries\LineString;
use Clickbar\Magellan\Data\Geometries\Point;

function searchParking(SearchParkingData $data)
{
    return app(SearchParking::class)->execute($data);
}

function locationAt(float $latitude, float $longitude): Location
{
    return Location::factory()->create([
        'coordinates' => Point::makeGeodetic(
            latitude: $latitude,
            longitude: $longitude,
        ),
    ]);
}

function streetGeometryAt(float $latitude, float $longitude): LineString
{
    return LineString::make([
        Point::makeGeodetic(
            latitude: $latitude - 0.00005,
            longitude: $longitude,
        ),
        Point::makeGeodetic(
            latitude: $latitude + 0.00005,
            longitude: $longitude,
        ),
    ]);
}

it('returns active facilities and street parking', function () {
    $facility = ParkingFacility::factory()->create([
        'location_id' => locationAt(25.5788, 91.8933)->id,
    ]);

    $street = StreetParking::factory()->create([
        'geometry' => streetGeometryAt(25.5790, 91.8935),
    ]);

    $results = searchParking(new SearchParkingData);

    expect($results->total())->toBe(2)
        ->and($results->getCollection()->pluck('parking.id'))
        ->toContain($facility->id)
        ->toContain($street->id);
});

it('filters by parking type', function () {
    $facility = ParkingFacility::factory()->create();
    $street = StreetParking::factory()->create();

    $facilities = searchParking(new SearchParkingData(
        type: 'facility',
    ));

    $streets = searchParking(new SearchParkingData(
        type: 'street',
    ));

    expect($facilities->total())->toBe(1)
        ->and($facilities->first()->parking)->toBeInstanceOf(ParkingFacility::class)
        ->and($facilities->first()->parking->is($facility))->toBeTrue()
        ->and($streets->total())->toBe(1)
        ->and($streets->first()->parking)->toBeInstanceOf(StreetParking::class)
        ->and($streets->first()->parking->is($street))->toBeTrue();
});

it('filters by availability status', function () {
    $available = ParkingFacility::factory()
        ->available(20)
        ->create();

    $full = ParkingFacility::factory()
        ->full()
        ->create();

    $results = searchParking(new SearchParkingData(
        availability: AvailabilityStatus::AVAILABLE,
    ));

    expect($results->total())->toBe(1)
        ->and($results->first()->parking->is($available))->toBeTrue()
        ->and($results->first()->parking->is($full))->toBeFalse();
});

it('filters by provider', function () {
    $provider = ParkingProvider::factory()->create();

    $matching = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    ParkingFacility::factory()->create();

    $results = searchParking(new SearchParkingData(
        providerId: $provider->id,
    ));

    expect($results->total())->toBe(1)
        ->and($results->first()->parking->is($matching))->toBeTrue();
});

it('excludes inactive parking', function () {
    $active = ParkingFacility::factory()->create();

    ParkingFacility::factory()->unavailable()->create();

    $results = searchParking(new SearchParkingData);

    expect($results->total())->toBe(1)
        ->and($results->first()->parking->is($active))->toBeTrue();
});

it('returns distance when coordinates are supplied', function () {
    $facility = ParkingFacility::factory()->create([
        'location_id' => locationAt(25.5788, 91.8933)->id,
    ]);

    $results = searchParking(new SearchParkingData(
        latitude: 25.5788,
        longitude: 91.8933,
    ));

    expect($results->total())->toBe(1)
        ->and($results->first()->parking->is($facility))->toBeTrue()
        ->and($results->first()->distanceMeters)
        ->toBeFloat()
        ->toBeLessThan(1.0);
});

it('orders spatial results by distance by default', function () {
    $near = ParkingFacility::factory()->create([
        'name' => 'Near',
        'location_id' => locationAt(25.5788, 91.8933)->id,
    ]);

    $far = ParkingFacility::factory()->create([
        'name' => 'Far',
        'location_id' => locationAt(25.5888, 91.9033)->id,
    ]);

    $results = searchParking(new SearchParkingData(
        latitude: 25.5788,
        longitude: 91.8933,
    ));

    expect($results->total())->toBe(2)
        ->and($results->getCollection()->first()->parking->is($near))->toBeTrue()
        ->and($results->getCollection()->last()->parking->is($far))->toBeTrue()
        ->and($results->getCollection()->first()->distanceMeters)
        ->toBeLessThan($results->getCollection()->last()->distanceMeters);
});

it('filters spatial results by radius', function () {
    $near = ParkingFacility::factory()->create([
        'location_id' => locationAt(25.5788, 91.8933)->id,
    ]);

    $far = ParkingFacility::factory()->create([
        'location_id' => locationAt(25.5888, 91.9033)->id,
    ]);

    $results = searchParking(new SearchParkingData(
        latitude: 25.5788,
        longitude: 91.8933,
        radiusMeters: 500,
    ));

    expect($results->total())->toBe(1)
        ->and($results->first()->parking->is($near))->toBeTrue()
        ->and($results->first()->parking->is($far))->toBeFalse();
});

it('returns null distance when no coordinates are supplied', function () {
    ParkingFacility::factory()->create();

    $results = searchParking(new SearchParkingData);

    expect($results->first()->distanceMeters)->toBeNull();
});

it('sorts by name when explicitly requested', function () {
    $zulu = ParkingFacility::factory()->create([
        'name' => 'Zulu Parking',
    ]);

    $alpha = ParkingFacility::factory()->create([
        'name' => 'Alpha Parking',
    ]);

    $results = searchParking(new SearchParkingData(
        sort: 'name',
    ));

    expect($results->getCollection()->pluck('parking.id')->all())
        ->toBe([
            $alpha->id,
            $zulu->id,
        ]);
});

it('sorts by capacity descending', function () {
    $small = ParkingFacility::factory()->create([
        'capacity' => 20,
    ]);

    $large = ParkingFacility::factory()->create([
        'capacity' => 100,
    ]);

    $results = searchParking(new SearchParkingData(
        sort: '-capacity',
    ));

    expect($results->getCollection()->pluck('parking.id')->all())
        ->toBe([
            $large->id,
            $small->id,
        ]);
});

it('sorts by available spaces descending', function () {
    $few = ParkingFacility::factory()->create([
        'available_spaces' => 2,
        'availability_status' => AvailabilityStatus::LIMITED,
    ]);

    $many = ParkingFacility::factory()->create([
        'available_spaces' => 15,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $results = searchParking(new SearchParkingData(
        sort: '-available_spaces',
    ));

    expect($results->getCollection()->pluck('parking.id')->all())
        ->toBe([
            $many->id,
            $few->id,
        ]);
});

it('paginates the combined facility and street results', function () {
    ParkingFacility::factory()->count(2)->create();
    StreetParking::factory()->count(2)->create();

    $page = searchParking(new SearchParkingData(
        perPage: 2,
        page: 1,
    ));

    expect($page->total())->toBe(4)
        ->and($page->perPage())->toBe(2)
        ->and($page->currentPage())->toBe(1)
        ->and($page->count())->toBe(2);
});

it('returns the requested second page', function () {
    ParkingFacility::factory()->count(2)->create();
    StreetParking::factory()->count(2)->create();

    $page = searchParking(new SearchParkingData(
        perPage: 2,
        page: 2,
    ));

    expect($page->total())->toBe(4)
        ->and($page->currentPage())->toBe(2)
        ->and($page->count())->toBe(2);
});
