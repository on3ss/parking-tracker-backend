<?php

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Exceptions\Parking\InvalidParkingAvailability;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

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

    expect($result->id)->toBe($facility->id);

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

    expect($result->id)->toBe($parking->id);

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

it('calculates availability status', function (int $capacity, int $available, AvailabilityStatus $expected, ) {
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

it('rejects invalid availability', function (int $capacity, int $available, ?int $occupied, string $message, ) {
    $facility = ParkingFacility::factory()->create([
        'capacity' => $capacity,
        'available_spaces' => $capacity,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    expect(fn() => app(ReportParkingAvailability::class)->execute(
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