<?php

use App\Enums\ParkingStatus;
use App\Enums\StreetParkingSide;
use App\Enums\StreetParkingType;
use App\Filament\Resources\StreetParkings\Pages\CreateStreetParking;
use App\Models\ParkingProvider;
use App\Models\StreetParking;
use App\Models\User;
use App\Support\Geo\GeoJson;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('creates street parking using the selected provider in the admin panel', function () {
    Filament::setCurrentPanel('admin');

    $provider = ParkingProvider::factory()->create();

    Livewire::test(CreateStreetParking::class)
        ->fillForm([
            'name' => 'Police Bazaar Street Parking',
            'parking_provider_id' => $provider->id,
            'road_name' => 'Police Bazaar Road',
            'side' => StreetParkingSide::LEFT->value,
            'parking_type' => StreetParkingType::CURBSIDE->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 20,
            'location' => [
                'address_line1' => 'Police Bazaar',
                'locality' => 'Shillong',
                'administrative_area' => 'Meghalaya',
                'postal_code' => '793001',
                'country_code' => 'IN',
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8930, 25.5785],
                    [91.8936, 25.5790],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parking = StreetParking::query()
        ->where('name', 'Police Bazaar Street Parking')
        ->firstOrFail();

    expect($parking->parking_provider_id)->toBe($provider->id)
        ->and($parking->location_id)->not->toBeNull();

    expect(GeoJson::from($parking->geometry))->toBe([
        'type' => 'LineString',
        'coordinates' => [
            [91.8930, 25.5785],
            [91.8936, 25.5790],
        ],
    ]);
});

it('creates street parking for the current provider tenant', function () {
    Filament::setCurrentPanel('provider');

    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(CreateStreetParking::class)
        ->fillForm([
            'name' => 'Police Bazaar Street Parking',
            'road_name' => 'Police Bazaar Road',
            'side' => StreetParkingSide::LEFT->value,
            'parking_type' => StreetParkingType::CURBSIDE->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 20,
            'location' => [
                'address_line1' => 'Police Bazaar',
                'locality' => 'Shillong',
                'administrative_area' => 'Meghalaya',
                'postal_code' => '793001',
                'country_code' => 'IN',
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8930, 25.5785],
                    [91.8936, 25.5790],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parking = StreetParking::query()
        ->where('name', 'Police Bazaar Street Parking')
        ->firstOrFail();

    expect($parking->parking_provider_id)->toBe($provider->id);
});

it('always assigns the current tenant as the provider', function () {
    Filament::setCurrentPanel('provider');

    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $otherProvider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(CreateStreetParking::class)
        ->fillForm([
            'name' => 'Tenant Street Parking',
            'road_name' => 'Tenant Road',
            'side' => StreetParkingSide::LEFT->value,
            'parking_type' => StreetParkingType::CURBSIDE->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 10,

            'parking_provider_id' => $otherProvider->id,

            'location' => [
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],

            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8930, 25.5785],
                    [91.8936, 25.5790],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parking = StreetParking::query()
        ->where('name', 'Tenant Street Parking')
        ->firstOrFail();

    expect($parking->parking_provider_id)
        ->toBe($provider->id)
        ->not->toBe($otherProvider->id);
});

it('defaults the location country to India', function () {
    Filament::setCurrentPanel('provider');

    $user = User::factory()->create();

    $provider = ParkingProvider::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Filament::setTenant($provider);

    Livewire::test(CreateStreetParking::class)
        ->fillForm([
            'name' => 'Shillong Street Parking',
            'road_name' => 'Shillong Road',
            'side' => StreetParkingSide::RIGHT->value,
            'parking_type' => StreetParkingType::CURBSIDE->value,
            'status' => ParkingStatus::ACTIVE->value,
            'capacity' => 10,
            'location' => [
                'latitude' => 25.5788,
                'longitude' => 91.8933,
            ],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [91.8930, 25.5785],
                    [91.8936, 25.5790],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $parking = StreetParking::query()
        ->where('name', 'Shillong Street Parking')
        ->firstOrFail();

    expect($parking->location->country_code)->toBe('IN');
});