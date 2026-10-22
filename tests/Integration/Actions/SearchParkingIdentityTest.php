<?php

use App\Actions\Parking\SearchParking;
use App\Data\Parking\SearchParkingData;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

it('hydrates overlapping facility and street IDs into the correct models', function () {
    $sharedId = 987654;

    $facility = ParkingFacility::factory()->create([
        'name' => 'Facility with shared id',
    ]);

    $street = StreetParking::factory()->create([
        'name' => 'Street with shared id',
    ]);

    $facility->id = $sharedId;
    $facility->save();

    $street->id = $sharedId;
    $street->save();

    $results = app(SearchParking::class)->execute(
        new SearchParkingData,
    );

    $facilityResult = $results->getCollection()->first(
        fn ($result) => $result->parking instanceof ParkingFacility,
    );

    $streetResult = $results->getCollection()->first(
        fn ($result) => $result->parking instanceof StreetParking,
    );

    expect($results->total())->toBe(2)
        ->and($facilityResult)->not->toBeNull()
        ->and($streetResult)->not->toBeNull()
        ->and($facilityResult->parking->id)->toBe($sharedId)
        ->and($facilityResult->parking->name)->toBe('Facility with shared id')
        ->and($streetResult->parking->id)->toBe($sharedId)
        ->and($streetResult->parking->name)->toBe('Street with shared id');
});
