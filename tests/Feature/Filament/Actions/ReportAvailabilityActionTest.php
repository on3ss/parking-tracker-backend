<?php

use App\Enums\ParkingSource;
use App\Filament\Resources\ParkingFacilities\Pages\ViewParkingFacility;
use App\Models\OccupancyReport;
use App\Models\ParkingFacility;
use App\Support\Rbac\Role;
use Livewire\Livewire;
use Tests\Support\ProviderTestContext;

it('reports availability from the parking facility action', function () {
    $context = ProviderTestContext::create(Role::Manager)
        ->actingAsUser();

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->id,
        'capacity' => 10,
        'available_spaces' => 10,
    ]);

    Livewire::test(ViewParkingFacility::class, [
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

    $report = OccupancyReport::query()
        ->find($facility->availability_report_id);

    expect($report)->not->toBeNull();
});

it('pre-fills the current availability', function () {
    $context = ProviderTestContext::create(Role::Manager)
        ->actingAsUser();

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->id,
        'capacity' => 10,
        'available_spaces' => 6,
    ]);

    Livewire::test(ViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->mountAction('reportAvailability')
        ->assertSchemaStateSet([
            'available_spaces' => 6,
        ]);
});

it('disables the action when capacity is not set', function () {
    $context = ProviderTestContext::create(Role::Manager)
        ->actingAsUser();

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->id,
        'capacity' => null,
    ]);

    Livewire::test(ViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->assertActionDisabled('reportAvailability');
});

it('allows a provider member to report availability for its own facility', function () {
    $context = ProviderTestContext::create(Role::Manager)
        ->actingAsUser();

    $facility = ParkingFacility::factory()->create([
        'parking_provider_id' => $context->provider->id,
        'capacity' => 10,
        'available_spaces' => 8,
    ]);

    Livewire::test(ViewParkingFacility::class, [
        'record' => $facility->getRouteKey(),
    ])
        ->callAction('reportAvailability', [
            'available_spaces' => 3,
        ])
        ->assertNotified();

    expect($facility->fresh()->available_spaces)->toBe(3);
});