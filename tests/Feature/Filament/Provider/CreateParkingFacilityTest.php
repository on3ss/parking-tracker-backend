<?php

use App\Enums\ParkingFacilityType;
use App\Enums\ParkingStatus;
use App\Filament\Provider\Resources\ParkingFacilities\Pages\CreateParkingFacility;
use App\Models\ParkingFacility;
use App\Models\ParkingProvider;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('creates a parking facility for the current provider tenant', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(CreateParkingFacility::class)
        ->fillForm([
            'name' => 'Police Bazaar Parking',
            'type' => ParkingFacilityType::PUBLIC->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 100,
            'location' => [
                'address_line1' => 'Police Bazaar',
                'locality' => 'Shillong',
                'administrative_area' => 'Meghalaya',
                'postal_code' => '793001',
                'country_code' => 'IN',
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $facility = ParkingFacility::query()
        ->where('name', 'Police Bazaar Parking')
        ->firstOrFail();

    expect($facility->parking_provider_id)->toBe($provider->id)
        ->and($facility->location_id)->not->toBeNull();

    $location = $facility->location;

    expect($location->address_line1)->toBe('Police Bazaar')
        ->and($location->locality)->toBe('Shillong')
        ->and($location->administrative_area)->toBe('Meghalaya')
        ->and($location->postal_code)->toBe('793001')
        ->and($location->country_code)->toBe('IN')
        ->and($location->coordinates->getLatitude())->toBe(25.5788)
        ->and($location->coordinates->getLongitude())->toBe(91.8933);
});

it('uses India as the default location country', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(CreateParkingFacility::class)
        ->fillForm([
            'name' => 'Shillong Parking',
            'type' => ParkingFacilityType::PUBLIC->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 50,
            'location' => [
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $facility = ParkingFacility::query()
        ->where('name', 'Shillong Parking')
        ->firstOrFail();

    expect($facility->location->country_code)->toBe('IN');
});

it('does not allow the form to choose a different provider', function () {
    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $otherProvider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(CreateParkingFacility::class)
        ->fillForm([
            'name' => 'Provider Parking',
            'type' => ParkingFacilityType::PUBLIC->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 50,

            // Deliberately attempt to inject another provider.
            'parking_provider_id' => $otherProvider->id,

            'location' => [
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $facility = ParkingFacility::query()
        ->where('name', 'Provider Parking')
        ->firstOrFail();

    expect($facility->parking_provider_id)->toBe($provider->id);
});
