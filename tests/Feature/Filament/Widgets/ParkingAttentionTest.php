<?php

use App\Enums\AvailabilityStatus;
use App\Enums\ParkingStatus;
use App\Filament\Admin\Widgets\ParkingAttention;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function getParkingAttentionStats(
    ParkingAttention $widget,
): array {
    $method = new ReflectionMethod(
        ParkingAttention::class,
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

it('counts stale records with a non-null timestamp regardless of parking status', function () {
    $provider = ParkingProvider::factory()->create();

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_updated_at' => now()->subHours(2),
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::INACTIVE,
        'availability_updated_at' => now()->subHours(2),
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_updated_at' => null,
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'status' => ParkingStatus::ACTIVE,
        'availability_updated_at' => now(),
    ]);

    $component = Livewire::test(ParkingAttention::class);

    $stats = getParkingAttentionStats($component->instance());

    expect($stats)
        ->toHaveCount(2)
        ->and($stats)->each->toBeInstanceOf(Stat::class);

    expect($stats[0]->getValue())->toBe(2);
});

it('counts unknown availability independently of timestamp freshness', function () {
    $provider = ParkingProvider::factory()->create();

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::UNKNOWN,
        'availability_updated_at' => now(),
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::UNKNOWN,
        'availability_updated_at' => null,
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::AVAILABLE,
        'availability_updated_at' => now()->subHours(2),
    ]);

    $component = Livewire::test(ParkingAttention::class);

    $stats = getParkingAttentionStats($component->instance());

    expect($stats[0]->getValue())->toBe(1)
        ->and($stats[1]->getValue())->toBe(2);
});

it('does not count a timestamp exactly at the one-hour cutoff as stale', function () {
    $provider = ParkingProvider::factory()->create();

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_updated_at' => now()->subHour(),
    ]);

    $component = Livewire::test(ParkingAttention::class);

    $stats = getParkingAttentionStats($component->instance());

    expect($stats[0]->getValue())->toBe(0);
});
