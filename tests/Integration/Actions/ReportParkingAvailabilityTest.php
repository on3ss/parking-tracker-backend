<?php

use App\Actions\Parking\ReportParkingAvailability;
use App\Data\Parking\ReportParkingAvailabilityData;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Exceptions\Parking\InvalidParkingAvailability;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;

function reportAvailability(
    string $parkingIdentifier,
    int $availableSpaces,
    ?int $occupiedSpaces = null,
    ParkingSource $source = ParkingSource::USER,
): \App\Data\Parking\ReportParkingAvailabilityResult {
    return app(ReportParkingAvailability::class)->execute(
        new ReportParkingAvailabilityData(
            parkingIdentifier: $parkingIdentifier,
            availableSpaces: $availableSpaces,
            occupiedSpaces: $occupiedSpaces,
        ),
        source: $source,
    );
}

it('reports availability for a parking facility', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 10,
    ]);

    $result = reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 4,
        occupiedSpaces: 6,
        source: ParkingSource::OPERATOR,
    );

    $parking = $result->parking->fresh();
    $report = $result->report->fresh();

    expect($report->parking_facility_id)->toBe($facility->id)
        ->and($report->street_parking_id)->toBeNull()
        ->and($report->available_spaces)->toBe(4)
        ->and($report->occupied_spaces)->toBe(6)
        ->and($report->source)->toBe(ParkingSource::OPERATOR)
        ->and($parking->available_spaces)->toBe(4)
        ->and($parking->availability_status)->toBe(AvailabilityStatus::AVAILABLE)
        ->and($parking->availability_source)->toBe(ParkingSource::OPERATOR)
        ->and($parking->availability_report_id)->toBe($report->id)
        ->and($parking->availability_updated_at->equalTo($report->reported_at))
        ->toBeTrue();
});

it('reports availability for street parking', function () {
    $parking = StreetParking::factory()->create([
        'capacity' => 10,
        'available_spaces' => 10,
    ]);

    $result = reportAvailability(
        parkingIdentifier: "street:{$parking->id}",
        availableSpaces: 2,
        occupiedSpaces: 8,
        source: ParkingSource::SENSOR,
    );

    $report = $result->report->fresh();
    $current = $result->parking->fresh();

    expect($report->street_parking_id)->toBe($parking->id)
        ->and($report->parking_facility_id)->toBeNull()
        ->and($current->available_spaces)->toBe(2)
        ->and($current->availability_status)->toBe(AvailabilityStatus::LIMITED)
        ->and($current->availability_source)->toBe(ParkingSource::SENSOR)
        ->and($current->availability_report_id)->toBe($report->id);
});

it('derives occupied spaces when omitted', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    $result = reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 3,
    );

    expect($result->report->occupied_spaces)->toBe(7);
});

it('preserves explicitly supplied occupied spaces', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    $result = reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 3,
        occupiedSpaces: 6,
    );

    expect($result->report->occupied_spaces)->toBe(6);
});

it('allows occupied and available spaces to exactly equal capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    $result = reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 4,
        occupiedSpaces: 6,
    );

    expect(
        $result->report->available_spaces
        + $result->report->occupied_spaces
    )->toBe($facility->capacity);
});

it('rejects reporting when parking capacity is not set', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => null,
    ]);

    reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 1,
    );
})->throws(InvalidParkingAvailability::class);

it('rejects negative available spaces', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: -1,
    );
})->throws(InvalidParkingAvailability::class);

it('rejects negative occupied spaces', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 4,
        occupiedSpaces: -1,
    );
})->throws(InvalidParkingAvailability::class);

it('rejects available spaces greater than capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 11,
    );
})->throws(InvalidParkingAvailability::class);

it('rejects occupied spaces greater than capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 1,
        occupiedSpaces: 11,
    );
})->throws(InvalidParkingAvailability::class);

it('rejects occupied and available spaces exceeding capacity', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 6,
        occupiedSpaces: 5,
    );
})->throws(InvalidParkingAvailability::class);

it('creates exactly one occupancy report and applies it to current availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    $result = reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 4,
        occupiedSpaces: 6,
    );

    expect(OccupancyReport::query()->count())->toBe(1)
        ->and($facility->fresh()->occupancyReports()->count())->toBe(1)
        ->and($facility->fresh()->availability_report_id)
        ->toBe($result->report->id);
});

it('keeps historical observations unchanged when a later report is submitted', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    $first = reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 8,
        occupiedSpaces: 2,
        source: ParkingSource::USER,
    )->report->fresh();

    $second = reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 3,
        occupiedSpaces: 7,
        source: ParkingSource::SENSOR,
    )->report->fresh();

    $current = $facility->fresh();

    expect(OccupancyReport::query()->count())->toBe(2)
        ->and($first->fresh()->available_spaces)->toBe(8)
        ->and($first->fresh()->occupied_spaces)->toBe(2)
        ->and($first->fresh()->source)->toBe(ParkingSource::USER)
        ->and($second->available_spaces)->toBe(3)
        ->and($current->available_spaces)->toBe(3)
        ->and($current->availability_source)->toBe(ParkingSource::SENSOR)
        ->and($current->availability_report_id)->toBe($second->id);
});

it('keeps facility and street parking targets isolated', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
    ]);

    $streetParking = StreetParking::factory()->create([
        'capacity' => 10,
    ]);

    reportAvailability(
        parkingIdentifier: "facility:{$facility->id}",
        availableSpaces: 7,
    );

    reportAvailability(
        parkingIdentifier: "street:{$streetParking->id}",
        availableSpaces: 2,
    );

    expect($facility->fresh()->available_spaces)->toBe(7)
        ->and($streetParking->fresh()->available_spaces)->toBe(2)
        ->and(
            OccupancyReport::query()
                ->where('parking_facility_id', $facility->id)
                ->count()
        )->toBe(1)
        ->and(
            OccupancyReport::query()
                ->where('street_parking_id', $streetParking->id)
                ->count()
        )->toBe(1);
});