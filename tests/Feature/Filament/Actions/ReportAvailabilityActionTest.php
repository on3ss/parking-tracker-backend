<?php

use App\Enums\ParkingSource;
use App\Filament\Admin\Resources\ParkingFacilities\Pages\ViewParkingFacility as AdminViewParkingFacility;
use App\Filament\Provider\Resources\ParkingFacilities\Pages\ViewParkingFacility as ProviderViewParkingFacility;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\ProviderMembership;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('reports availability from the admin parking facility action', function () {
    $user = User::factory()->create();

    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 10,
    ]);

    $this->actingAs($user);

    Livewire::test(AdminViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->callAction('reportAvailability', [
            'available_spaces' => 4,
        ])
        ->assertNotified();

    $facility->refresh();

    expect($facility->available_spaces)->toBe(4)
        ->and($facility->availability_source)->toBe(ParkingSource::OPERATOR)
        ->and($facility->availability_report_id)->not->toBeNull();

    expect(
        OccupancyReport::query()
            ->where('id', $facility->availability_report_id)
            ->exists()
    )->toBeTrue();
});

it('pre-fills the current availability', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => 10,
        'available_spaces' => 6,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(AdminViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->mountAction('reportAvailability')
        ->assertSchemaStateSet([
            'available_spaces' => 6,
        ]);
});

it('disables the action when capacity is not set', function () {
    $facility = ParkingFacility::factory()->create([
        'capacity' => null,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(AdminViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->assertActionDisabled('reportAvailability');
});

it('allows a provider to report availability for its own parking facility', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $provider->id,
        'capacity' => 10,
        'available_spaces' => 8,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(ProviderViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->callAction('reportAvailability', [
            'available_spaces' => 3,
        ])
        ->assertNotified();

    expect($facility->fresh()->available_spaces)->toBe(3);
});

it('does not authorize a provider to report availability for another provider parking facility', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $otherProvider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    ProviderMembership::create([
        'user_id' => $user->id,
        'parking_provider_id' => $provider->id,
    ]);

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $otherProvider->id,
        'capacity' => 10,
        'available_spaces' => 8,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(ProviderViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->assertActionHidden('reportAvailability');
});
