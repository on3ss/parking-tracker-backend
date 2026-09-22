<?php

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Exceptions\Parking\InvalidParkingAvailability;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

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

    expect($facility->availability_status)
        ->toBe(AvailabilityStatus::AVAILABLE);

    $report = OccupancyReport::query()
        ->where('parking_facility_id', $facility->id)
        ->latest('id')
        ->first();

    expect($report)->not->toBeNull();
    expect($report->available_spaces)->toBe(12);
    expect($report->occupied_spaces)->toBe(38);
    expect($report->source)->toBe(ParkingSource::USER);
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

    expect($parking->availability_status)
        ->toBe(AvailabilityStatus::LIMITED);

    $report = OccupancyReport::query()
        ->where('street_parking_id', $parking->id)
        ->latest('id')
        ->first();

    expect($report)->not->toBeNull();
    expect($report->available_spaces)->toBe(2);
    expect($report->occupied_spaces)->toBe(8);
    expect($report->source)->toBe(ParkingSource::USER);
});

it('rejects availability greater than capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 10,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    expect(fn() => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 11,
        ),
    ))->toThrow(
            InvalidParkingAvailability::class,
            'Available spaces cannot exceed parking capacity.',
        );

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists(),
    )->toBeFalse();

    $facility->refresh();

    expect($facility->available_spaces)->toBe(10);
});

it('rejects inconsistent occupied and available spaces', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 20,
        'available_spaces' => 20,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    expect(fn() => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 15,
            occupiedSpaces: 10,
        ),
    ))->toThrow(
            InvalidParkingAvailability::class,
            'Occupied and available spaces cannot exceed parking capacity.',
        );

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists(),
    )->toBeFalse();

    $facility->refresh();

    expect($facility->available_spaces)->toBe(20);
});

it('rejects available spaces greater than capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 10,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    expect(fn() => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 11,
        ),
    ))->toThrow(
            InvalidParkingAvailability::class,
            'Available spaces cannot exceed parking capacity.',
        );

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists()
    )->toBeFalse();

    $facility->refresh();

    expect($facility->available_spaces)->toBe(10);
});

it('rejects occupied spaces greater than capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 10,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    expect(fn() => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 2,
            occupiedSpaces: 11,
        ),
    ))->toThrow(
            InvalidParkingAvailability::class,
            'Occupied spaces cannot exceed parking capacity.',
        );

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists()
    )->toBeFalse();
});

it('rejects occupied and available spaces exceeding capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 20,
        'available_spaces' => 20,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    expect(fn() => app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 15,
            occupiedSpaces: 10,
        ),
    ))->toThrow(
            InvalidParkingAvailability::class,
            'Occupied and available spaces cannot exceed parking capacity.',
        );

    expect(
        OccupancyReport::query()
            ->where('parking_facility_id', $facility->id)
            ->exists()
    )->toBeFalse();

    $facility->refresh();

    expect($facility->available_spaces)->toBe(20);
});

it('marks parking as full when no spaces are available', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 20,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 0,
        ),
    );

    $facility->refresh();

    expect($facility->available_spaces)->toBe(0);
    expect($facility->availability_status)->toBe(AvailabilityStatus::FULL);
});

it('marks parking as limited at twenty percent availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 50,
        'available_spaces' => 50,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 10,
        ),
    );

    $facility->refresh();

    expect($facility->available_spaces)->toBe(10);
    expect($facility->availability_status)->toBe(AvailabilityStatus::LIMITED);
});

it('marks parking as limited below twenty percent availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 100,
        'available_spaces' => 100,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 19,
        ),
    );

    $facility->refresh();

    expect($facility->availability_status)->toBe(AvailabilityStatus::LIMITED);
});

it('marks parking as available above twenty percent availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 100,
        'available_spaces' => 10,
        'availability_status' => AvailabilityStatus::LIMITED,
    ]);

    app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 21,
        ),
    );

    $facility->refresh();

    expect($facility->available_spaces)->toBe(21);
    expect($facility->availability_status)->toBe(AvailabilityStatus::AVAILABLE);
});

it('marks zero capacity parking as unknown', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 0,
        'available_spaces' => 0,
        'availability_status' => AvailabilityStatus::UNKNOWN,
    ]);

    app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: "facility:{$facility->id}",
            availableSpaces: 0,
        ),
    );

    $facility->refresh();

    expect($facility->availability_status)->toBe(AvailabilityStatus::UNKNOWN);
});
