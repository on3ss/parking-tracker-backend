<?php

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Exceptions\Parking\InvalidParkingAvailability;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use App\Support\Parking\ParkingIdentifier;

/*
|--------------------------------------------------------------------------
| State transitions
|--------------------------------------------------------------------------
*/

it('creates an occupancy report and updates facility availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $result = app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 12,
        ),
    );

    expect($result->parking->id)->toBe($facility->id);

    $facility->refresh();

    expect($facility->available_spaces)->toBe(12);
    expect($facility->availability_status)->toBe(AvailabilityStatus::AVAILABLE);

    $report = OccupancyReport::query()
        ->where('parking_facility_id', $facility->id)
        ->latest('id')
        ->first();

    expect($report)->not->toBeNull();
    expect($report->available_spaces)->toBe(12);
    expect($report->occupied_spaces)->toBe(38);
    expect($report->source)->toBe(ParkingSource::USER);
    expect($report->reported_at)->not->toBeNull();
});

it('creates an occupancy report and updates street parking availability', function () {
    $parking = StreetParking::factory()->create([
        'capacity' => 10,
        'available_spaces' => 5,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $result = app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "street:{$parking->id}",
            availableSpaces: 2,
        ),
    );

    expect($result->parking->id)->toBe($parking->id);

    $parking->refresh();

    expect($parking->available_spaces)->toBe(2);
    expect($parking->availability_status)->toBe(AvailabilityStatus::LIMITED);

    $report = OccupancyReport::query()
        ->where('street_parking_id', $parking->id)
        ->latest('id')
        ->first();

    expect($report)->not->toBeNull();
    expect($report->available_spaces)->toBe(2);
    expect($report->occupied_spaces)->toBe(8);
    expect($report->source)->toBe(ParkingSource::USER);
});

/*
|--------------------------------------------------------------------------
| Availability status calculation
|--------------------------------------------------------------------------
*/

it('calculates availability status', function (int $capacity, int $available, AvailabilityStatus $expected) {
    $facility = ParkingFacility::factory()->create([
        'capacity' => $capacity,
        'available_spaces' => $capacity,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: $available,
        ),
    );

    expect($facility->refresh()->availability_status)->toBe($expected);
})->with([
    'full' => [50, 0, AvailabilityStatus::FULL],
    'at threshold' => [50, 10, AvailabilityStatus::LIMITED],
    'below threshold' => [100, 19, AvailabilityStatus::LIMITED],
    'above threshold' => [100, 21, AvailabilityStatus::AVAILABLE],
    'zero capacity' => [0, 0, AvailabilityStatus::UNKNOWN],
]);

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

it('rejects invalid availability', function (int $capacity, int $available, ?int $occupied, string $message) {
    $facility = ParkingFacility::factory()->create([
        'capacity' => $capacity,
        'available_spaces' => $capacity,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    expect(fn () => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: $available,
            occupiedSpaces: $occupied,
        ),
    ))->toThrow(InvalidParkingAvailability::class, $message);

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists(),
    )->toBeFalse();

    $facility->refresh();

    expect($facility->available_spaces)->toBe($capacity);
})->with([
    'available exceeds capacity' => [
        10,
        11,
        null,
        'Available spaces cannot exceed parking capacity.',
    ],
    'occupied exceeds capacity' => [
        10,
        2,
        11,
        'Occupied spaces cannot exceed parking capacity.',
    ],
    'occupied plus available exceeds capacity' => [
        20,
        15,
        10,
        'Occupied and available spaces cannot exceed parking capacity.',
    ],
]);

it('rejects negative available spaces', function () {
    $parking = ParkingFacility::factory()->create([
        'capacity' => 20,
        'available_spaces' => 10,
    ]);

    expect(fn () => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: ParkingIdentifier::for($parking),
            availableSpaces: -1,
        ),
    ))
        ->toThrow(
            InvalidParkingAvailability::class,
            'Available spaces cannot be negative.',
        );
});

it('rejects negative occupied spaces', function () {
    $parking = ParkingFacility::factory()->create([
        'capacity' => 20,
        'available_spaces' => 10,
    ]);

    expect(fn () => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: ParkingIdentifier::for($parking),
            availableSpaces: 10,
            occupiedSpaces: -1,
        ),
    ))
        ->toThrow(
            InvalidParkingAvailability::class,
            'Occupied spaces cannot be negative.',
        );
});

it('rejects availability reports when capacity is not set', function () {
    $parking = ParkingFacility::factory()->create([
        'capacity' => null,
        'available_spaces' => null,
    ]);

    expect(fn () => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: ParkingIdentifier::for($parking),
            availableSpaces: 5,
        ),
    ))
        ->toThrow(
            InvalidParkingAvailability::class,
            'Parking capacity must be set before availability can be reported.',
        );
});

it('records but does not apply a user report when a fresh sensor observation is active', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 30,
        'availability_source' => ParkingSource::SENSOR,
        'availability_updated_at' => now()->subMinute(),
    ]);

    $result = app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 0,
        ),
        source: ParkingSource::USER,
    );

    expect($result->accepted)->toBeFalse();
    expect($facility->refresh()->available_spaces)->toBe(30);
    expect($facility->availability_source)->toBe(ParkingSource::SENSOR);

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->count(),
    )->toBe(1);
});

it('applies a user report over a stale sensor observation', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 30,
        'availability_source' => ParkingSource::SENSOR,
        'availability_updated_at' => now()->subHour(),
    ]);

    $result = app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 12,
        ),
        source: ParkingSource::USER,
    );

    expect($result->accepted)->toBeTrue();
    expect($facility->refresh()->available_spaces)->toBe(12);
    expect($facility->availability_source)->toBe(ParkingSource::USER);
    expect($facility->availability_report_id)->toBe($result->report->id);
});

it('applies an operator report over a fresh user observation', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 40,
        'availability_source' => ParkingSource::USER,
        'availability_updated_at' => now()->subMinute(),
    ]);

    $result = app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 5,
        ),
        source: ParkingSource::OPERATOR,
    );

    expect($result->accepted)->toBeTrue();
    expect($facility->refresh()->availability_source)
        ->toBe(ParkingSource::OPERATOR);
});
