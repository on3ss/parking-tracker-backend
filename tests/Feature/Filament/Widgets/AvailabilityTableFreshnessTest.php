<?php

use App\Filament\Admin\Widgets\FacilityAvailabilityTable;
use App\Filament\Admin\Widgets\StreetParkingAvailabilityTable;
use App\Models\ParkingFacility;
use App\Models\StreetParking;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config([
        'parking.availability_stale_after_minutes' => 30,
    ]);

    Carbon::setTestNow(
        Carbon::parse('2026-10-22 12:00:00'),
    );
});

afterEach(function () {
    Carbon::setTestNow();

    config([
        'parking.availability_stale_after_minutes' => 60,
    ]);
});

/**
 * Invoke the widget's freshness decision for a given record.
 */
function availabilityTableRecordIsStale(
    object $widget,
    object $record,
): bool {
    $method = new ReflectionMethod($widget, 'isStale');

    return $method->invoke($widget, $record);
}

dataset('availability table widgets', [
    'facility table' => [
        FacilityAvailabilityTable::class,
        ParkingFacility::class,
    ],
    'street parking table' => [
        StreetParkingAvailabilityTable::class,
        StreetParking::class,
    ],
]);

it('marks never-reported availability as stale', function (string $widgetClass, string $modelClass) {
    $record = new $modelClass([
        'availability_updated_at' => null,
    ]);

    expect(
        availabilityTableRecordIsStale(
            new $widgetClass,
            $record,
        ),
    )->toBeTrue();
})->with('availability table widgets');

it('uses the configured threshold to determine stale availability', function (string $widgetClass, string $modelClass) {
    $widget = new $widgetClass;

    $staleRecord = new $modelClass([
        'availability_updated_at' => now()->subMinutes(31),
    ]);

    $freshRecord = new $modelClass([
        'availability_updated_at' => now()->subMinutes(29),
    ]);

    expect(
        availabilityTableRecordIsStale($widget, $staleRecord),
    )->toBeTrue()
        ->and(
            availabilityTableRecordIsStale($widget, $freshRecord),
        )->toBeFalse();
})->with('availability table widgets');

it('treats availability exactly at the cutoff as fresh', function (string $widgetClass, string $modelClass) {
    $record = new $modelClass([
        'availability_updated_at' => now()->subMinutes(30),
    ]);

    expect(
        availabilityTableRecordIsStale(
            new $widgetClass,
            $record,
        ),
    )->toBeFalse();
})->with('availability table widgets');
