<?php

use App\Actions\Parking\RecordOccupancyObservation;
use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Models\User;
use Carbon\CarbonImmutable;

it('records an occupancy observation for a parking facility', function () {
    $facility = ParkingFacility::factory()->create();
    $reportedAt = CarbonImmutable::parse('2026-10-05 10:30:00');

    $report = app(RecordOccupancyObservation::class)->execute(
        parking: $facility,
        source: ParkingSource::USER,
        userId: null,
        availableSpaces: 4,
        occupiedSpaces: 6,
        reportedAt: $reportedAt,
    );

    expect($report)
        ->toBeInstanceOf(OccupancyReport::class)
        ->and($report->parking_facility_id)->toBe($facility->id)
        ->and($report->street_parking_id)->toBeNull()
        ->and($report->user_id)->toBeNull()
        ->and($report->source)->toBe(ParkingSource::USER)
        ->and($report->available_spaces)->toBe(4)
        ->and($report->occupied_spaces)->toBe(6)
        ->and($report->reported_at->equalTo($reportedAt))->toBeTrue();

    expect(
        OccupancyReport::query()->whereKey($report->id)->exists()
    )->toBeTrue();
});

it('records an occupancy observation for street parking', function () {
    $streetParking = StreetParking::factory()->create();
    $reportedAt = CarbonImmutable::parse('2026-10-05 11:00:00');

    $report = app(RecordOccupancyObservation::class)->execute(
        parking: $streetParking,
        source: ParkingSource::SENSOR,
        userId: null,
        availableSpaces: 2,
        occupiedSpaces: 8,
        reportedAt: $reportedAt,
    );

    expect($report->street_parking_id)->toBe($streetParking->id)
        ->and($report->parking_facility_id)->toBeNull()
        ->and($report->source)->toBe(ParkingSource::SENSOR)
        ->and($report->available_spaces)->toBe(2)
        ->and($report->occupied_spaces)->toBe(8)
        ->and($report->reported_at->equalTo($reportedAt))->toBeTrue();
});

it('records the reporting user when supplied', function () {
    $facility = ParkingFacility::factory()->create();
    $user = User::factory()->create();

    $report = app(RecordOccupancyObservation::class)->execute(
        parking: $facility,
        source: ParkingSource::USER,
        userId: $user->id,
        availableSpaces: 5,
        occupiedSpaces: 5,
        reportedAt: now(),
    );

    expect($report->user_id)->toBe($user->id)
        ->and($report->user->is($user))->toBeTrue();
});

it('allows an observation without a reporting user', function () {
    $facility = ParkingFacility::factory()->create();

    $report = app(RecordOccupancyObservation::class)->execute(
        parking: $facility,
        source: ParkingSource::SENSOR,
        userId: null,
        availableSpaces: 5,
        occupiedSpaces: 5,
        reportedAt: now(),
    );

    expect($report->user_id)->toBeNull();
});

it('preserves the supplied source', function (ParkingSource $source) {
    $facility = ParkingFacility::factory()->create();

    $report = app(RecordOccupancyObservation::class)->execute(
        parking: $facility,
        source: $source,
        userId: null,
        availableSpaces: 5,
        occupiedSpaces: 5,
        reportedAt: now(),
    );

    expect($report->source)->toBe($source);
})->with([
    ParkingSource::USER,
    ParkingSource::OPERATOR,
    ParkingSource::SENSOR,
    ParkingSource::CAMERA,
    ParkingSource::SYSTEM,
]);

it('persists the exact reported timestamp', function () {
    $facility = ParkingFacility::factory()->create();
    $reportedAt = CarbonImmutable::parse('2026-01-15 08:42:17');

    $report = app(RecordOccupancyObservation::class)->execute(
        parking: $facility,
        source: ParkingSource::OPERATOR,
        userId: null,
        availableSpaces: 7,
        occupiedSpaces: 3,
        reportedAt: $reportedAt,
    );

    $persisted = OccupancyReport::query()->findOrFail($report->id);

    expect($persisted->reported_at->equalTo($reportedAt))->toBeTrue();
});

it('does not modify the parking current availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 9,
    ]);

    app(RecordOccupancyObservation::class)->execute(
        parking: $facility,
        source: ParkingSource::USER,
        userId: null,
        availableSpaces: 3,
        occupiedSpaces: 7,
        reportedAt: now(),
    );

    $facility->refresh();

    expect($facility->available_spaces)->toBe(9);
});
