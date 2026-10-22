<?php

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Filament\Admin\Widgets\AvailabilitySummary;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Livewire;
use Illuminate\Support\Carbon;

function getAvailabilitySummaryStats(
    AvailabilitySummary $widget,
): array {
    $method = new ReflectionMethod(
        AvailabilitySummary::class,
        'getStats',
    );

    return $method->invoke($widget);
}

beforeEach(function () {
    Carbon::setTestNow(
        Carbon::parse('2026-10-22 12:00:00'),
    );
});

afterEach(function () {
    Carbon::setTestNow();
});

it('counts availability statuses across active parking only', function () {
    $provider = ParkingProvider::factory()->create();

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_status' => AvailabilityStatus::AVAILABLE,
        'availability_updated_at' => now(),
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_status' => AvailabilityStatus::LIMITED,
        'availability_updated_at' => now(),
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_status' => AvailabilityStatus::FULL,
        'availability_updated_at' => now(),
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::INACTIVE,
        'availability_status' => AvailabilityStatus::FULL,
        'availability_updated_at' => now(),
    ]);

    $component = Livewire::test(AvailabilitySummary::class);

    $stats = getAvailabilitySummaryStats($component->instance());

    expect($stats)
        ->toHaveCount(5)
        ->and($stats)->each->toBeInstanceOf(Stat::class);

    expect($stats[0]->getValue())->toBe(1)
        ->and($stats[1]->getValue())->toBe(1)
        ->and($stats[2]->getValue())->toBe(1)
        ->and($stats[3]->getValue())->toBe(0)
        ->and($stats[4]->getValue())->toBe(0);
});

it('counts active parking with stale or never-reported availability', function () {
    $provider = ParkingProvider::factory()->create();

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_updated_at' => null,
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_updated_at' => now()->subHour()->subSecond(),
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::INACTIVE,
        'availability_updated_at' => now()->subHours(2),
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_updated_at' => now(),
    ]);

    $component = Livewire::test(AvailabilitySummary::class);

    $stats = getAvailabilitySummaryStats($component->instance());

    expect($stats[4]->getValue())->toBe(2);
});

it('does not count availability exactly at the one-hour cutoff as stale', function () {
    $provider = ParkingProvider::factory()->create();

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_updated_at' => now()->subHour(),
    ]);

    $component = Livewire::test(AvailabilitySummary::class);

    $stats = getAvailabilitySummaryStats($component->instance());

    expect($stats[4]->getValue())->toBe(0);
});
