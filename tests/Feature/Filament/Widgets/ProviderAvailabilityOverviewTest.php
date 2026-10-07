<?php

use App\Enums\AvailabilityStatus;
use App\Filament\Provider\Widgets\ProviderAvailabilityOverview;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Livewire;

function getProviderAvailabilityStats(
    ProviderAvailabilityOverview $widget,
): array {
    $method = new ReflectionMethod(
        ProviderAvailabilityOverview::class,
        'getStats',
    );

    return $method->invoke($widget);
}

it('returns no stats when there is no tenant', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test(ProviderAvailabilityOverview::class);

    expect(
        getProviderAvailabilityStats($component->instance()),
    )->toBeEmpty();
});

it('aggregates availability across facilities and street parking', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::LIMITED,
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::FULL,
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::UNKNOWN,
    ]);

    $component = Livewire::test(ProviderAvailabilityOverview::class);

    $stats = getProviderAvailabilityStats($component->instance());

    expect($stats)->toHaveCount(4)
        ->and($stats)->each->toBeInstanceOf(Stat::class);

    expect($stats[0]->getValue())->toBe('2')
        ->and($stats[1]->getValue())->toBe('1')
        ->and($stats[2]->getValue())->toBe('1')
        ->and($stats[3]->getValue())->toBe('1');
});

it('excludes parking belonging to another provider', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create();
    $otherProvider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $otherProvider->id,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    StreetParking::factory()->create([
        'parking_provider_id' => $otherProvider->id,
        'availability_status' => AvailabilityStatus::FULL,
    ]);

    $component = Livewire::test(ProviderAvailabilityOverview::class);

    $stats = getProviderAvailabilityStats($component->instance());

    expect($stats[0]->getValue())->toBe('1')
        ->and($stats[2]->getValue())->toBe('0');
});

it('includes zero counts for statuses with no parking', function () {
    $user = User::factory()->create();
    $provider = ParkingProvider::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($provider);

    ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'availability_status' => AvailabilityStatus::AVAILABLE,
    ]);

    $component = Livewire::test(ProviderAvailabilityOverview::class);

    $stats = getProviderAvailabilityStats($component->instance());

    expect($stats[0]->getValue())->toBe('1')
        ->and($stats[1]->getValue())->toBe('0')
        ->and($stats[2]->getValue())->toBe('0')
        ->and($stats[3]->getValue())->toBe('0');
});
