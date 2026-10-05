<?php

use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Database\QueryException;

it('allows an observation for a parking facility', function () {
    $facility = ParkingFacility::factory()->create();

    $report = OccupancyReport::factory()
        ->forFacility($facility)
        ->create();

    expect($report->parking_facility_id)->toBe($facility->id)
        ->and($report->street_parking_id)->toBeNull();
});

it('allows an observation for street parking', function () {
    $streetParking = StreetParking::factory()->create();

    $report = OccupancyReport::factory()
        ->forStreetParking($streetParking)
        ->create();

    expect($report->parking_facility_id)->toBeNull()
        ->and($report->street_parking_id)->toBe($streetParking->id);
});

it('rejects an observation without a parking target', function () {
    OccupancyReport::query()->create([
        'parking_facility_id' => null,
        'street_parking_id' => null,
        'user_id' => null,
        'source' => ParkingSource::USER,
        'available_spaces' => 5,
        'occupied_spaces' => 5,
        'reported_at' => now(),
    ]);
})->throws(QueryException::class);

it('rejects an observation with both parking targets', function () {
    $facility = ParkingFacility::factory()->create();
    $streetParking = StreetParking::factory()->create();

    OccupancyReport::query()->create([
        'parking_facility_id' => $facility->id,
        'street_parking_id' => $streetParking->id,
        'user_id' => null,
        'source' => ParkingSource::USER,
        'available_spaces' => 5,
        'occupied_spaces' => 5,
        'reported_at' => now(),
    ]);
})->throws(QueryException::class);

