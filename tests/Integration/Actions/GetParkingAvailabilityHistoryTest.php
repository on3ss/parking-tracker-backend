<?php

use App\Actions\Parking\GetParkingAvailabilityHistory;
use App\Data\Parking\GetParkingAvailabilityHistoryData;
use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Carbon\Carbon;

function getAvailabilityHistory(
    string $parkingIdentifier,
    int $perPage = 20,
    int $page = 1,
) {
    return app(GetParkingAvailabilityHistory::class)->execute(
        new GetParkingAvailabilityHistoryData(
            parkingIdentifier: $parkingIdentifier,
            perPage: $perPage,
            page: $page,
        ),
    );
}

it('returns facility availability history in newest first order', function () {
    $facility = ParkingFacility::factory()->create();

    $older = OccupancyReport::factory()
        ->forFacility($facility)
        ->create([
            'available_spaces' => 20,
            'reported_at' => Carbon::parse('2026-10-01 10:00:00'),
        ]);

    $newer = OccupancyReport::factory()
        ->forFacility($facility)
        ->create([
            'available_spaces' => 10,
            'reported_at' => Carbon::parse('2026-10-01 11:00:00'),
        ]);

    $history = getAvailabilityHistory(
        "facility:{$facility->id}",
    );

    expect($history->items())
        ->toHaveCount(2)
        ->and($history->first()->is($newer))->toBeTrue()
        ->and($history->last()->is($older))->toBeTrue();
});

it('returns street parking availability history', function () {
    $streetParking = StreetParking::factory()->create();

    $report = OccupancyReport::factory()
        ->forStreetParking($streetParking)
        ->create([
            'available_spaces' => 4,
        ]);

    $history = getAvailabilityHistory(
        "street:{$streetParking->id}",
    );

    expect($history->items())
        ->toHaveCount(1)
        ->and($history->first()->is($report))->toBeTrue();
});

it('only returns history for the requested parking', function () {
    $first = ParkingFacility::factory()->create();
    $second = ParkingFacility::factory()->create();

    $report = OccupancyReport::factory()
        ->forFacility($first)
        ->create();

    OccupancyReport::factory()
        ->forFacility($second)
        ->create();

    $history = getAvailabilityHistory(
        "facility:{$first->id}",
    );

    expect($history->items())
        ->toHaveCount(1)
        ->and($history->first()->is($report))->toBeTrue();
});

it('does not mix facility and street parking ids', function () {
    $facility = ParkingFacility::factory()->create();
    $streetParking = StreetParking::factory()->create();

    $facilityReport = OccupancyReport::factory()
        ->forFacility($facility)
        ->create();

    OccupancyReport::factory()
        ->forStreetParking($streetParking)
        ->create();

    $history = getAvailabilityHistory(
        "facility:{$facility->id}",
    );

    expect($history->items())
        ->toHaveCount(1)
        ->and($history->first()->is($facilityReport))->toBeTrue();
});

it('preserves report data in history', function () {
    $facility = ParkingFacility::factory()->create();

    $reportedAt = Carbon::parse('2026-10-01 12:34:56');

    $report = OccupancyReport::factory()
        ->forFacility($facility)
        ->create([
            'source' => ParkingSource::SENSOR,
            'occupied_spaces' => 80,
            'available_spaces' => 20,
            'reported_at' => $reportedAt,
        ]);

    $history = getAvailabilityHistory(
        "facility:{$facility->id}",
    );

    $result = $history->first();

    expect($result->id)->toBe($report->id)
        ->and($result->source)->toBe(ParkingSource::SENSOR)
        ->and($result->occupied_spaces)->toBe(80)
        ->and($result->available_spaces)->toBe(20)
        ->and($result->reported_at->equalTo($reportedAt))->toBeTrue();
});

it('paginates history', function () {
    $facility = ParkingFacility::factory()->create();

    foreach (range(1, 5) as $index) {
        OccupancyReport::factory()
            ->forFacility($facility)
            ->create([
                'reported_at' => Carbon::parse(
                    "2026-10-01 10:0{$index}:00",
                ),
            ]);
    }

    $history = getAvailabilityHistory(
        "facility:{$facility->id}",
        perPage: 2,
        page: 2,
    );

    expect($history->perPage())->toBe(2)
        ->and($history->currentPage())->toBe(2)
        ->and($history->total())->toBe(5)
        ->and($history->items())->toHaveCount(2);
});

it('uses report id as a deterministic tie breaker', function () {
    $facility = ParkingFacility::factory()->create();

    $reportedAt = Carbon::parse('2026-10-01 12:00:00');

    $first = OccupancyReport::factory()
        ->forFacility($facility)
        ->create([
            'reported_at' => $reportedAt,
        ]);

    $second = OccupancyReport::factory()
        ->forFacility($facility)
        ->create([
            'reported_at' => $reportedAt,
        ]);

    $history = getAvailabilityHistory(
        "facility:{$facility->id}",
    );

    expect($history->items())
        ->toHaveCount(2)
        ->and($history->first()->is($second))->toBeTrue()
        ->and($history->last()->is($first))->toBeTrue();
});

it('throws when the parking identifier does not exist', function () {
    expect(fn() => getAvailabilityHistory('facility:999999'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws for an invalid parking identifier', function () {
    expect(fn() => getAvailabilityHistory('invalid:1'))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});