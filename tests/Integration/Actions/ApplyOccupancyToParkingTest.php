<?php

use App\Actions\Parking\ApplyOccupancyToParking;
use App\Enums\AvailabilityStatus;
use App\Enums\ParkingSource;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use Carbon\Carbon;

function applyOccupancyReport(
    ParkingFacility $facility,
    string $reportedAt,
    int $availableSpaces,
    ParkingSource $source = ParkingSource::OPERATOR,
): OccupancyReport {
    return OccupancyReport::factory()
        ->forFacility($facility)
        ->create([
            'available_spaces' => $availableSpaces,
            'occupied_spaces' => $facility->capacity - $availableSpaces,
            'source' => $source,
            'reported_at' => Carbon::parse($reportedAt),
        ]);
}

it('accepts the first report when current availability has no timestamp', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 10,
        'availability_updated_at' => null,
        'availability_report_id' => null,
    ]);

    $report = applyOccupancyReport(
        $facility,
        '2026-01-01 10:00:00',
        4,
    );

    expect(
        app(ApplyOccupancyToParking::class)
            ->execute($facility->fresh(), $report),
    )->toBeTrue();

    $current = $facility->fresh();

    expect($current->available_spaces)->toBe(4)
        ->and($current->availability_status)->toBe(AvailabilityStatus::AVAILABLE)
        ->and($current->availability_updated_at->equalTo($report->reported_at))->toBeTrue()
        ->and($current->availability_source)->toBe(ParkingSource::OPERATOR)
        ->and($current->availability_report_id)->toBe($report->id);
});

it('accepts a newer report and updates current availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 8,
        'availability_updated_at' => Carbon::parse('2026-01-01 10:00:00'),
    ]);

    $report = applyOccupancyReport(
        $facility,
        '2026-01-01 10:05:00',
        2,
        ParkingSource::SENSOR,
    );

    $accepted = app(ApplyOccupancyToParking::class)
        ->execute($facility->fresh(), $report);

    $current = $facility->fresh();

    expect($accepted)->toBeTrue()
        ->and($current->available_spaces)->toBe(2)
        ->and($current->availability_status)->toBe(AvailabilityStatus::LIMITED)
        ->and($current->availability_updated_at->equalTo($report->reported_at))->toBeTrue()
        ->and($current->availability_source)->toBe(ParkingSource::SENSOR)
        ->and($current->availability_report_id)->toBe($report->id);
});

it('rejects an older report without changing current availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 3,
        'availability_status' => AvailabilityStatus::LIMITED,
        'availability_source' => ParkingSource::SENSOR,
        'availability_updated_at' => Carbon::parse('2026-01-01 10:05:00'),
    ]);

    $currentReport = applyOccupancyReport(
        $facility,
        '2026-01-01 10:05:00',
        3,
        ParkingSource::SENSOR,
    );

    $facility->update([
        'availability_report_id' => $currentReport->id,
    ]);

    $staleReport = applyOccupancyReport(
        $facility,
        '2026-01-01 10:00:00',
        8,
        ParkingSource::OPERATOR,
    );

    $before = $facility->fresh();

    $accepted = app(ApplyOccupancyToParking::class)
        ->execute($facility->fresh(), $staleReport);

    $after = $facility->fresh();

    expect($accepted)->toBeFalse()
        ->and($after->available_spaces)->toBe($before->available_spaces)
        ->and($after->availability_status)->toBe($before->availability_status)
        ->and($after->availability_updated_at->equalTo($before->availability_updated_at))->toBeTrue()
        ->and($after->availability_source)->toBe($before->availability_source)
        ->and($after->availability_report_id)->toBe($before->availability_report_id)
        ->and($staleReport->fresh())->not->toBeNull();
});

it('accepts a report with the same timestamp as the current state', function () {
    $timestamp = Carbon::parse('2026-01-01 10:00:00');

    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 8,
        'availability_updated_at' => $timestamp,
    ]);

    $report = applyOccupancyReport(
        $facility,
        '2026-01-01 10:00:00',
        5,
        ParkingSource::OPERATOR,
    );

    $accepted = app(ApplyOccupancyToParking::class)
        ->execute($facility->fresh(), $report);

    expect($accepted)->toBeTrue()
        ->and($facility->fresh()->available_spaces)->toBe(5)
        ->and($facility->fresh()->availability_report_id)->toBe($report->id);
});
