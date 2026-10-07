<?php

use App\Filament\Provider\Widgets\ProviderStatsOverview;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Livewire;
use ReflectionMethod;

function getProviderStats(ProviderStatsOverview $widget): array
{
    $method = new ReflectionMethod(
        ProviderStatsOverview::class,
        'getStats',
    );

    return $method->invoke($widget);
}

it('returns no stats when there is no tenant', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(ProviderStatsOverview::class);

    expect(getProviderStats($component->instance()))
        ->toBeEmpty();
});

it('shows facility and street parking counts', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()
        ->count(2)
        ->create([
            'parking_provider_id' => $provider->id,
        ]);

    StreetParking::factory()
        ->count(3)
        ->create([
            'parking_provider_id' => $provider->id,
        ]);

    $component = Livewire::test(ProviderStatsOverview::class);

    $stats = getProviderStats($component->instance());

    expect($stats)->toHaveCount(4)
        ->and($stats)->each->toBeInstanceOf(Stat::class)
        ->and($stats[0]->getValue())->toBe('2')
        ->and($stats[1]->getValue())->toBe('3');
});

it('shows combined parking capacity', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'capacity' => 100,
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'capacity' => 50,
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'capacity' => 20,
    ]);

    $component = Livewire::test(ProviderStatsOverview::class);

    $stats = getProviderStats($component->instance());

    expect($stats[2]->getValue())->toBe('170');
});

it('counts occupancy reports for the current provider today', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    $streetParking = StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    OccupancyReport::factory()
        ->count(2)
        ->create([
            'parking_facility_id' => $facility->id,
            'reported_at' => now(),
        ]);

    OccupancyReport::factory()->create([
        'street_parking_id' => $streetParking->id,
        'reported_at' => now(),
    ]);

    $component = Livewire::test(ProviderStatsOverview::class);

    $stats = getProviderStats($component->instance());

    expect($stats[3]->getValue())->toBe('3');
});

it('does not count reports from another provider', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create();
    $otherProvider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    $otherFacility = ParkingFacility::factory()->create([
        'parking_provider_id' => $otherProvider->id,
    ]);

    OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
        'reported_at' => now(),
    ]);

    OccupancyReport::factory()->create([
        'parking_facility_id' => $otherFacility->id,
        'reported_at' => now(),
    ]);

    $component = Livewire::test(ProviderStatsOverview::class);

    $stats = getProviderStats($component->instance());

    expect($stats[3]->getValue())->toBe('1');
});

it('does not count reports from previous days', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
    ]);

    OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
        'reported_at' => now(),
    ]);

    OccupancyReport::factory()->create([
        'parking_facility_id' => $facility->id,
        'reported_at' => now()->subDay(),
    ]);

    $component = Livewire::test(ProviderStatsOverview::class);

    $stats = getProviderStats($component->instance());

    expect($stats[3]->getValue())->toBe('1');
});

it('includes zero counts when the provider has no parking', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    $component = Livewire::test(ProviderStatsOverview::class);

    $stats = getProviderStats($component->instance());

    expect($stats[0]->getValue())->toBe('0')
        ->and($stats[1]->getValue())->toBe('0')
        ->and($stats[2]->getValue())->toBe('0')
        ->and($stats[3]->getValue())->toBe('0');
});